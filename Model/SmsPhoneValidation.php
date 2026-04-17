<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Model;

use Magebit\KlaviyoSubscription\Api\SmsPhoneValidationInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Pattern-based validation for Klaviyo SMS regions (E.164 digit strings, no leading +).
 *
 * NANP (US/CA): accepts 10-digit national or 11-digit starting with 1.
 * Other regions: primarily full country-code prefix; UK/AU/NZ include common local 0-prefix forms.
 */
class SmsPhoneValidation implements SmsPhoneValidationInterface
{
    private const NANP_NATIONAL_10 = '/^[2-9]\d{2}[2-9]\d{6}$/';

    private const NANP_INTL_11 = '/^1[2-9]\d{2}[2-9]\d{6}$/';

    /**
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @inheritdoc
     */
    public function isValidForConfiguredRegions(string $phoneNumber): bool
    {
        return $this->getInternationalNumberOrNull($phoneNumber) !== null;
    }

    /**
     * @inheritdoc
     */
    public function getInternationalNumberOrNull(string $phoneNumber): ?string
    {
        $enabled = $this->config->getEnabledSmsCountries(
            (int) $this->storeManager->getStore()->getId()
        );

        if ($enabled === []) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if ($digits === '') {
            return null;
        }

        $expanded = $this->expandDigitCandidates($digits, $enabled);

        foreach (array_unique($expanded) as $candidate) {
            if ($candidate === '') {
                continue;
            }

            foreach ($enabled as $iso) {
                $international = $this->matchCountry((string) $iso, $candidate);
                if ($international !== null) {
                    return '+' . $international;
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $enabled
     * @return array<int, string>
     */
    private function expandDigitCandidates(string $digits, array $enabled): array
    {
        $candidates = [$digits];

        $nanpEnabled = $this->isNanpEnabled($enabled);
        if ($nanpEnabled && strlen($digits) === 11 && $digits[0] === '1') {
            $candidates[] = substr($digits, 1);
        }

        return $candidates;
    }

    /**
     * @param array<int, string> $enabled
     */
    private function isNanpEnabled(array $enabled): bool
    {
        return in_array('US', $enabled, true) || in_array('CA', $enabled, true);
    }

    private function matchCountry(string $iso, string $digits): ?string
    {
        return match ($iso) {
            'US', 'CA' => $this->matchNanp($digits),
            'GB' => $this->matchGb($digits),
            'AU' => $this->matchAu($digits),
            'NZ' => $this->matchNz($digits),
            'AT' => $this->matchIntlRegex($digits, '/^43\d{8,12}$/'),
            'BE' => $this->matchIntlRegex($digits, '/^32\d{8,9}$/'),
            'DK' => $this->matchIntlRegex($digits, '/^45\d{8}$/'),
            'FI' => $this->matchIntlRegex($digits, '/^358\d{6,10}$/'),
            'FR' => $this->matchFr($digits),
            'DE' => $this->matchIntlRegex($digits, '/^49\d{10,12}$/'),
            'HU' => $this->matchIntlRegex($digits, '/^36\d{8,9}$/'),
            'IE' => $this->matchIntlRegex($digits, '/^353\d{7,9}$/'),
            'IT' => $this->matchIntlRegex($digits, '/^39\d{8,10}$/'),
            'LU' => $this->matchIntlRegex($digits, '/^352\d{6,11}$/'),
            'NL' => $this->matchNl($digits),
            'NO' => $this->matchIntlRegex($digits, '/^47\d{8}$/'),
            'PL' => $this->matchIntlRegex($digits, '/^48\d{9}$/'),
            'PT' => $this->matchIntlRegex($digits, '/^351\d{9}$/'),
            'ES' => $this->matchEs($digits),
            'SE' => $this->matchIntlRegex($digits, '/^46\d{8,10}$/'),
            'CH' => $this->matchIntlRegex($digits, '/^41\d{9}$/'),
            default => null,
        };
    }

    private function matchIntlRegex(string $digits, string $pattern): ?string
    {
        if (preg_match($pattern, $digits)) {
            return $digits;
        }

        return null;
    }

    private function matchNanp(string $digits): ?string
    {
        if (preg_match(self::NANP_NATIONAL_10, $digits)) {
            return '1' . $digits;
        }

        if (preg_match(self::NANP_INTL_11, $digits)) {
            return $digits;
        }

        return null;
    }

    private function matchGb(string $digits): ?string
    {
        if (preg_match('/^44[1-9]\d{9}$/', $digits)) {
            return $digits;
        }

        if (preg_match('/^0[1-9]\d{9}$/', $digits)) {
            return '44' . substr($digits, 1);
        }

        return null;
    }

    private function matchAu(string $digits): ?string
    {
        if (preg_match('/^61[2-478]\d{8}$/', $digits)) {
            return $digits;
        }

        if (preg_match('/^0[2-478]\d{8}$/', $digits)) {
            return '61' . substr($digits, 1);
        }

        return null;
    }

    private function matchNz(string $digits): ?string
    {
        if (preg_match('/^64\d{8,10}$/', $digits)) {
            return $digits;
        }

        if (preg_match('/^0\d{8,10}$/', $digits)) {
            return '64' . substr($digits, 1);
        }

        return null;
    }

    private function matchFr(string $digits): ?string
    {
        if (preg_match('/^33[1-9]\d{8}$/', $digits)) {
            return $digits;
        }

        if (preg_match('/^0[1-9]\d{8}$/', $digits)) {
            return '33' . substr($digits, 1);
        }

        return null;
    }

    private function matchNl(string $digits): ?string
    {
        if (preg_match('/^31[1-9]\d{8}$/', $digits)) {
            return $digits;
        }

        if (preg_match('/^0[1-9]\d{8}$/', $digits)) {
            return '31' . substr($digits, 1);
        }

        return null;
    }

    private function matchEs(string $digits): ?string
    {
        if (preg_match('/^34[1-9]\d{8}$/', $digits)) {
            return $digits;
        }

        return null;
    }
}
