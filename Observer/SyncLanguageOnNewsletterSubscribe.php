<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Observer;

use Magebit\KlaviyoSubscription\Model\ProfileLanguageSync;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;

/**
 * Sends the language whenever a subscriber is handed to Klaviyo.
 *
 * Rides the same event as {@see \Klaviyo\Reclaim\Observer\NewsletterSubscribeObserver} and repeats
 * its guards, so we push exactly when the extension itself pushes the profile — no extra API calls
 * on unrelated subscriber saves. The store comes from the subscriber row, not from the current
 * store: subscribers are also created from the admin, from imports and from cron.
 */
class SyncLanguageOnNewsletterSubscribe implements ObserverInterface
{
    /**
     * @param ProfileLanguageSync $profileLanguageSync
     */
    public function __construct(
        private readonly ProfileLanguageSync $profileLanguageSync
    ) {
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $subscriber = $observer->getDataObject();
        if (!$subscriber instanceof Subscriber || !$subscriber->getId()) {
            return;
        }

        if (!$subscriber->isStatusChanged() && !$subscriber->isObjectNew()) {
            return;
        }

        if ((int) $subscriber->getStatus() !== Subscriber::STATUS_SUBSCRIBED) {
            return;
        }

        $this->profileLanguageSync->sync((string) $subscriber->getEmail(), (int) $subscriber->getStoreId());
    }
}
