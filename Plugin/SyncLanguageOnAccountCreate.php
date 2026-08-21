<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Plugin;

use Magebit\KlaviyoSubscription\Model\ProfileLanguageSync;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Sends the language for every newly registered customer.
 *
 * Hooked on `createAccount` rather than on the `customer_register_success` event because every
 * registration path funnels through it — the storefront controller, checkout registration, social
 * login and the REST API — while that event is dispatched only by the storefront controller.
 */
class SyncLanguageOnAccountCreate
{
    /**
     * @param ProfileLanguageSync $profileLanguageSync
     */
    public function __construct(
        private readonly ProfileLanguageSync $profileLanguageSync
    ) {
    }

    /**
     * @param AccountManagementInterface $subject
     * @param CustomerInterface $result
     * @return CustomerInterface
     */
    public function afterCreateAccount(
        AccountManagementInterface $subject,
        CustomerInterface $result
    ): CustomerInterface {
        $storeId = $result->getStoreId();
        $this->profileLanguageSync->sync(
            (string) $result->getEmail(),
            $storeId === null ? null : (int) $storeId
        );

        return $result;
    }
}
