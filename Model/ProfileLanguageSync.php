<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Model;

use Magebit\KlaviyoSubscription\Model\Api\ProfileClient;
use Magebit\KlaviyoSubscription\Model\Data\ProfileLanguage;
use Psr\Log\LoggerInterface;

/**
 * Writes a store view's language onto a Klaviyo profile.
 *
 * Both representations go out together: the native `locale` attribute (Klaviyo's own field, usable
 * by any language-aware logic) and the custom `Language` property the current flow splits read.
 */
class ProfileLanguageSync
{
    /**
     * Custom profile property name — must stay in step with the conditional splits in Klaviyo
     */
    public const PROPERTY_LANGUAGE = 'Language';

    /**
     * @var array<string, true>
     */
    private array $synced = [];

    /**
     * @param Config $config
     * @param LanguageResolver $languageResolver
     * @param ProfileClient $profileClient
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Config $config,
        private readonly LanguageResolver $languageResolver,
        private readonly ProfileClient $profileClient,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Push the store view's language onto the profile with this email.
     *
     * Failures are logged and swallowed: this runs inside customer and subscriber saves, and a
     * Klaviyo outage must never fail a registration.
     *
     * @param string $email
     * @param int|null $storeId
     * @return void
     */
    public function sync(string $email, ?int $storeId): void
    {
        $email = trim($email);
        if ($email === '' || !$this->config->isKlaviyoEnabled($storeId)) {
            return;
        }

        $apiKey = $this->config->getPrivateApiKey($storeId);
        $language = $this->languageResolver->resolve($storeId);
        if ($apiKey === '' || $language === null) {
            return;
        }

        // One request per email/locale pair per PHP request: a single save can be observed more
        // than once, and Magebit\Customer re-saves the customer entity during address handling.
        $guard = strtolower($email) . '|' . $language->getLocale();
        if (isset($this->synced[$guard])) {
            return;
        }
        $this->synced[$guard] = true;

        try {
            $this->profileClient->importProfile($apiKey, $this->buildAttributes($email, $language));
        } catch (\Throwable $e) {
            unset($this->synced[$guard]);
            $this->logger->error(
                sprintf('[Magebit_Klaviyo] Could not send language for %s: %s', $email, $e->getMessage())
            );
        }
    }

    /**
     * Profile attributes for the import endpoints
     *
     * @param string $email
     * @param ProfileLanguage $language
     * @return array<string, mixed>
     */
    public function buildAttributes(string $email, ProfileLanguage $language): array
    {
        return [
            'email' => $email,
            'locale' => $language->getLocale(),
            'properties' => [
                self::PROPERTY_LANGUAGE => $language->getLanguage(),
            ],
        ];
    }
}
