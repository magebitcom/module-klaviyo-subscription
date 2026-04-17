<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Klaviyo SMS-supported countries per Klaviyo Help Center (ISO 3166-1 alpha-2).
 *
 * @see https://help.klaviyo.com/hc/en-us/articles/4402914866843
 */
class KlaviyoSmsCountries implements OptionSourceInterface
{
    /**
     * @inheritdoc
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->getCountries() as $code => $label) {
            $options[] = ['value' => $code, 'label' => __($label)];
        }

        return $options;
    }

    /**
     * @return array<string, string> code => English label
     */
    public function getCountries(): array
    {
        return [
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'AU' => 'Australia',
            'NZ' => 'New Zealand',
            'AT' => 'Austria',
            'BE' => 'Belgium',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'FR' => 'France',
            'DE' => 'Germany',
            'HU' => 'Hungary',
            'IE' => 'Ireland',
            'IT' => 'Italy',
            'LU' => 'Luxembourg',
            'NL' => 'Netherlands',
            'NO' => 'Norway',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'ES' => 'Spain',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
        ];
    }
}
