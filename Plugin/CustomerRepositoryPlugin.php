<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerSearchResultsInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;

/**
 * Plugin to add subscription to customer
 */
class CustomerRepositoryPlugin
{
    /**
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     */
    public function __construct(
        private readonly ExtensionAttributesFactory $extensionAttributesFactory
    ) {
    }

    /**
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface $customer
     * @return CustomerInterface
     */
    public function afterGetById(
        CustomerRepositoryInterface $subject,
        CustomerInterface &$customer
    ): CustomerInterface {
        return $this->addIsSmsSubscribedExtensionAttribute($customer);
    }

    /**
     * @param CustomerRepositoryInterface $subject
     * @param CustomerSearchResultsInterface $searchResults
     * @return CustomerSearchResultsInterface
     */
    public function afterGetList(
        CustomerRepositoryInterface $subject,
        CustomerSearchResultsInterface $searchResults
    ): CustomerSearchResultsInterface {
        foreach ($searchResults->getItems() as $customer) {
            $this->addIsSmsSubscribedExtensionAttribute($customer);
        }

        return $searchResults;
    }

    /**
     * @param CustomerInterface $customer
     * @return CustomerInterface
     */
    private function addIsSmsSubscribedExtensionAttribute(CustomerInterface $customer): CustomerInterface
    {
        $extensionAttributes = $customer->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionAttributesFactory->create(CustomerInterface::class);
        }

        /** @phpstan-ignore-next-line */
        $extensionAttributes->setIsSmsSubscribed(
            $customer->getCustomAttribute('is_sms_subscribed') ?
                (bool)$customer->getCustomAttribute('is_sms_subscribed')->getValue() :
                false
        );
        $customer->setExtensionAttributes($extensionAttributes);

        return $customer;
    }
}
