<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\ViewModel;

use Magebit\KlaviyoSubscription\Model\LanguageResolver;
use Magebit\KlaviyoSubscription\Model\ProfileLanguageSync;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Language of the store view being browsed, for the onsite `klaviyo.identify()` payload.
 *
 * Only the custom `Language` property is exposed. The onsite tracker promotes `$`-prefixed keys to
 * native profile fields and has no documented `$locale`, so the native attribute is left to the
 * server-side paths that use /api/profile-import.
 */
class ProfileLanguage implements ArgumentInterface
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param LanguageResolver $languageResolver
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly LanguageResolver $languageResolver
    ) {
    }

    /**
     * Identify payload fragment, or an empty array when the locale is unusable
     *
     * @return array<string, string>
     */
    public function getIdentifyProperties(): array
    {
        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
            return [];
        }

        $language = $this->languageResolver->resolve($storeId);
        if ($language === null) {
            return [];
        }

        return [ProfileLanguageSync::PROPERTY_LANGUAGE => $language->getLanguage()];
    }
}
