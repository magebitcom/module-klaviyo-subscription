<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Model;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

/**
 * Module configuration (store-scoped).
 */
class Config
{
    public const XML_PATH_SMS_SUBSCRIPTION_ENABLED = 'magebit_klaviyo/sms_validation/sms_subscription_enabled';

    public const XML_PATH_SMS_VALIDATION_COUNTRIES = 'magebit_klaviyo/sms_validation/countries';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @return bool
     */
    public function isSmsEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_SMS_SUBSCRIPTION_ENABLED);
    }

    /**
     * ISO 3166-1 alpha-2 codes enabled for SMS phone validation.
     *
     * @param int|null $storeId
     * @return array<int, string>
     */
    public function getEnabledSmsCountries(?int $storeId = null): array
    {
        $value = (string) $this->scopeConfig->getValue(
            self::XML_PATH_SMS_VALIDATION_COUNTRIES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($value === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $value));

        return array_values(array_filter($parts, static fn (string $code): bool => $code !== ''));
    }

    /**
     * Is the Klaviyo extension itself enabled for this store
     *
     * A store id that no longer exists counts as disabled rather than throwing — see
     * {@see LanguageResolver::resolve()} for why stale store ids reach us at all.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isKlaviyoEnabled(?int $storeId): bool
    {
        if ($storeId === null) {
            return $this->scopeConfig->isSetFlag(ScopeSetting::ENABLE);
        }

        try {
            return $this->scopeConfig->isSetFlag(
                ScopeSetting::ENABLE,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Klaviyo private API key. Magento decrypts obscure fields on read, so this is the pk_ value.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getPrivateApiKey(?int $storeId): string
    {
        if ($storeId === null) {
            return trim((string) $this->scopeConfig->getValue(ScopeSetting::PRIVATE_API_KEY));
        }

        try {
            return trim((string) $this->scopeConfig->getValue(
                ScopeSetting::PRIVATE_API_KEY,
                ScopeInterface::SCOPE_STORE,
                $storeId
            ));
        } catch (NoSuchEntityException $e) {
            return '';
        }
    }
}
