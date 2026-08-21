<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Plugin;

use Klaviyo\Reclaim\Model\Events;
use Magebit\KlaviyoSubscription\Model\LanguageResolver;
use Magebit\KlaviyoSubscription\Model\ProfileLanguageSync;

/**
 * Carries the language on Klaviyo events so guest profiles get it too.
 *
 * Guests are identified only by the `$exchange_id` from the __kla_id cookie
 * ({@see \Klaviyo\Reclaim\Observer\SalesQuoteSaveAfter}), and `/api/profile-import` needs an email,
 * so the event's own customer properties are the only way to reach them. Setting it on the queued
 * row rather than at send time covers every event topic the extension ever adds.
 *
 * Only the short `Language` property is set, deliberately.
 * {@see \Klaviyo\Reclaim\KlaviyoV3Sdk\KlaviyoV3Api::buildCustomerProperties} promotes just email,
 * firstname, lastname and $exchange_id to native profile attributes and nests everything else under
 * `properties` — a `locale` key sent this way would become a custom property of that name rather
 * than Klaviyo's native locale field, which is worse than not sending it.
 */
class AddLanguageToKlaviyoEvent
{
    /**
     * @param LanguageResolver $languageResolver
     */
    public function __construct(
        private readonly LanguageResolver $languageResolver
    ) {
    }

    /**
     * @param Events $subject
     * @return void
     */
    public function beforeSave(Events $subject): void
    {
        $userProperties = json_decode((string) $subject->getData('user_properties'), true);
        if (!is_array($userProperties) || $userProperties === []) {
            return;
        }

        if (isset($userProperties[ProfileLanguageSync::PROPERTY_LANGUAGE])) {
            return;
        }

        $payload = json_decode((string) $subject->getData('payload'), true);
        $storeId = is_array($payload) && isset($payload['StoreId']) ? (int) $payload['StoreId'] : null;

        $language = $this->languageResolver->resolve($storeId);
        if ($language === null) {
            return;
        }

        $userProperties[ProfileLanguageSync::PROPERTY_LANGUAGE] = $language->getLanguage();
        $subject->setData('user_properties', json_encode($userProperties));
    }
}
