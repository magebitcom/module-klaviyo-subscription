<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Model;

use Magebit\KlaviyoSubscription\Model\Data\ProfileLanguage;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

/**
 * Turns a store id into the language values sent to Klaviyo.
 *
 * Everything is derived from `general/locale/code` at store scope, so adding a store view needs
 * no change here. On this project that resolves to en-GB / en for store 1 and lv-LV / lv for
 * store 2 — note the English store is en_GB, not en_US.
 */
class LanguageResolver
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Resolve the language of a store view, or null when the store has no usable locale.
     *
     * A null or admin (0) store id falls back to the default-scope locale, which is what
     * programmatic saves outside a store context should be attributed to.
     *
     * A store id that no longer exists resolves to null rather than throwing: Magento leaves
     * `newsletter_subscriber.store_id` pointing at deleted store views, and a stale row must not
     * break the subscribe it is riding along with.
     *
     * @param int|null $storeId
     * @return ProfileLanguage|null
     */
    public function resolve(?int $storeId): ?ProfileLanguage
    {
        try {
            $locale = $storeId === null || $storeId === 0
                ? (string) $this->scopeConfig->getValue(DirectoryHelper::XML_PATH_DEFAULT_LOCALE)
                : (string) $this->scopeConfig->getValue(
                    DirectoryHelper::XML_PATH_DEFAULT_LOCALE,
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                );
        } catch (NoSuchEntityException $e) {
            return null;
        }

        $locale = trim($locale);
        if (!preg_match('/^([a-z]{2,3})[_-]([A-Za-z0-9]{2,4})$/i', $locale, $matches)) {
            return null;
        }

        return new ProfileLanguage(
            strtolower($matches[1]) . '-' . strtoupper($matches[2]),
            strtolower($matches[1])
        );
    }
}
