<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Module configuration (store-scoped).
 */
class Config
{
    public const XML_PATH_SMS_VALIDATION_COUNTRIES = 'magebit_klaviyo/sms_validation/countries';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
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
}
