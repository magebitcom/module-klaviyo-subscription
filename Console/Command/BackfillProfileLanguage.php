<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Console\Command;

use Magebit\KlaviyoSubscription\Model\Api\ProfileClient;
use Magebit\KlaviyoSubscription\Model\Config;
use Magebit\KlaviyoSubscription\Model\LanguageResolver;
use Magebit\KlaviyoSubscription\Model\ProfileLanguageSync;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Magento\Newsletter\Model\Subscriber;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Backfills the language of every profile Klaviyo already knows about.
 *
 * Without this the flow splits keep failing for the entire existing audience: profiles created
 * before Magebit_Klaviyo shipped carry no language, and nothing re-sends it for a customer who
 * never registers or subscribes again.
 */
class BackfillProfileLanguage extends Command
{
    private const OPTION_DRY_RUN = 'dry-run';
    private const OPTION_BATCH_SIZE = 'batch-size';
    private const OPTION_STORE = 'store';
    private const DEFAULT_BATCH_SIZE = 1000;

    /**
     * @param Config $config
     * @param LanguageResolver $languageResolver
     * @param ProfileLanguageSync $profileLanguageSync
     * @param ProfileClient $profileClient
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param SubscriberCollectionFactory $subscriberCollectionFactory
     * @param string|null $name
     */
    public function __construct(
        private readonly Config $config,
        private readonly LanguageResolver $languageResolver,
        private readonly ProfileLanguageSync $profileLanguageSync,
        private readonly ProfileClient $profileClient,
        private readonly CustomerCollectionFactory $customerCollectionFactory,
        private readonly SubscriberCollectionFactory $subscriberCollectionFactory,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('magebit:klaviyo:backfill-profile-language');
        $this->setDescription('Send the store view language of every customer and subscriber to Klaviyo');
        $this->addOption(
            self::OPTION_DRY_RUN,
            null,
            InputOption::VALUE_NONE,
            'Report what would be sent without calling Klaviyo'
        );
        $this->addOption(
            self::OPTION_BATCH_SIZE,
            null,
            InputOption::VALUE_REQUIRED,
            'Profiles per bulk import job (max ' . ProfileClient::BULK_IMPORT_LIMIT . ')',
            (string) self::DEFAULT_BATCH_SIZE
        );
        $this->addOption(
            self::OPTION_STORE,
            null,
            InputOption::VALUE_REQUIRED,
            'Limit to one store id'
        );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = (int) $input->getOption(self::OPTION_BATCH_SIZE);
        if ($batchSize < 1 || $batchSize > ProfileClient::BULK_IMPORT_LIMIT) {
            $output->writeln(sprintf(
                '<error>--batch-size must be between 1 and %d.</error>',
                ProfileClient::BULK_IMPORT_LIMIT
            ));

            return Command::FAILURE;
        }

        $storeFilter = $input->getOption(self::OPTION_STORE);
        $storeFilter = $storeFilter === null ? null : (int) $storeFilter;
        $isDryRun = (bool) $input->getOption(self::OPTION_DRY_RUN);

        $emailsByStore = $this->collectEmailsByStore($storeFilter);
        if ($emailsByStore === []) {
            $output->writeln('<comment>No customers or subscribers found.</comment>');

            return Command::SUCCESS;
        }

        $exitCode = Command::SUCCESS;

        foreach ($emailsByStore as $storeId => $emails) {
            $language = $this->languageResolver->resolve($storeId);
            if ($language === null) {
                $output->writeln(sprintf('<error>Store %d has no usable locale, skipped.</error>', $storeId));
                $exitCode = Command::FAILURE;
                continue;
            }

            $apiKey = $this->config->getPrivateApiKey($storeId);
            if (!$this->config->isKlaviyoEnabled($storeId) || $apiKey === '') {
                $output->writeln(sprintf('<error>Store %d has no enabled Klaviyo scope, skipped.</error>', $storeId));
                $exitCode = Command::FAILURE;
                continue;
            }

            $output->writeln(sprintf(
                '<info>Store %d: %d profile(s) -> locale %s, %s "%s"</info>',
                $storeId,
                count($emails),
                $language->getLocale(),
                ProfileLanguageSync::PROPERTY_LANGUAGE,
                $language->getLanguage()
            ));

            if ($isDryRun) {
                continue;
            }

            foreach (array_chunk($emails, $batchSize) as $index => $chunk) {
                $profiles = array_map(
                    fn (string $email): array => $this->profileLanguageSync->buildAttributes($email, $language),
                    $chunk
                );

                try {
                    $this->profileClient->bulkImportProfiles($apiKey, $profiles);
                    $output->writeln(sprintf('  batch %d: %d profile(s) queued', $index + 1, count($chunk)));
                } catch (\Throwable $e) {
                    $output->writeln(sprintf('  <error>batch %d failed: %s</error>', $index + 1, $e->getMessage()));
                    $exitCode = Command::FAILURE;
                }
            }
        }

        return $exitCode;
    }

    /**
     * Emails grouped by the store view whose language they should get.
     *
     * Customers win over newsletter subscribers for the same address: the account's store view is
     * the language the shopper chose for themselves, while a subscriber row can predate it.
     *
     * @param int|null $storeFilter
     * @return array<int, array<int, string>>
     */
    private function collectEmailsByStore(?int $storeFilter): array
    {
        $storeByEmail = [];

        $subscribers = $this->subscriberCollectionFactory->create();
        $subscribers->addFieldToSelect(['subscriber_email', 'store_id']);
        $subscribers->addFieldToFilter('subscriber_status', ['eq' => Subscriber::STATUS_SUBSCRIBED]);
        if ($storeFilter !== null) {
            $subscribers->addFieldToFilter('store_id', ['eq' => $storeFilter]);
        }

        foreach ($subscribers as $subscriber) {
            $email = strtolower(trim((string) $subscriber->getData('subscriber_email')));
            if ($email !== '') {
                $storeByEmail[$email] = (int) $subscriber->getData('store_id');
            }
        }

        $customers = $this->customerCollectionFactory->create();
        $customers->addFieldToSelect('email');
        $customers->addFieldToSelect('store_id');
        if ($storeFilter !== null) {
            $customers->addFieldToFilter('store_id', ['eq' => $storeFilter]);
        }

        foreach ($customers as $customer) {
            $email = strtolower(trim((string) $customer->getData('email')));
            if ($email !== '') {
                $storeByEmail[$email] = (int) $customer->getData('store_id');
            }
        }

        $emailsByStore = [];
        foreach ($storeByEmail as $email => $storeId) {
            $emailsByStore[$storeId][] = (string) $email;
        }

        ksort($emailsByStore);

        return $emailsByStore;
    }
}
