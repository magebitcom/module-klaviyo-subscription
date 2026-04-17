<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Plugin;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Plugin to add subscriptions to quote
 */
class QuoteRepositoryPlugin
{
    /**
     * @param CartRepositoryInterface $subject
     * @param CartInterface $quote
     * @return CartInterface
     */
    public function afterGet(
        CartRepositoryInterface $subject,
        CartInterface $quote
    ): CartInterface {
        $extensionAttributes = $quote->getExtensionAttributes();

        // @phpstan-ignore-next-line
        $extensionAttributes->setGeneralSubscription($quote->getData('general_subscription'));
        // @phpstan-ignore-next-line
        $extensionAttributes->setSmsSubscription($quote->getData('sms_subscription'));

        $quote->setExtensionAttributes($extensionAttributes);

        return $quote;
    }

    /**
     * @param CartRepositoryInterface $subject
     * @param CartInterface $quote
     * @return array
     */
    public function beforeSave(
        CartRepositoryInterface $subject,
        CartInterface $quote
    ): array {
        $extensionAttributes = $quote->getExtensionAttributes();
        if ($extensionAttributes !== null) {

            // @phpstan-ignore-next-line
            $quote->setData('general_subscription', $extensionAttributes->getGeneralSubscription());
            // @phpstan-ignore-next-line
            $quote->setData('sms_subscription', $extensionAttributes->getSmsSubscription());
        }

        return [$quote];
    }
}
