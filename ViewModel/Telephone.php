<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\ViewModel;

use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Hyva\Theme\ViewModel\SvgIcons;

/**
 * Viewmodel for getting list of countries with phone codes
 */
class Telephone implements ArgumentInterface
{
    public const COUNTRY_PHONE_CODES = [
        [
            'phoneCode' => '376',
            'value' => 'AD',
        ],
        [
            'phoneCode' => '971',
            'value' => 'AE',
        ],
        [
            'phoneCode' => '93',
            'value' => 'AF',
        ],
        [
            'phoneCode' => '1',
            'value' => 'AG',
        ],
        [
            'phoneCode' => '1',
            'value' => 'AI',
        ],
        [
            'phoneCode' => '355',
            'value' => 'AL',
        ],
        [
            'phoneCode' => '374',
            'value' => 'AM',
        ],
        [
            'phoneCode' => '244',
            'value' => 'AO',
        ],
        [
            'phoneCode' => '672',
            'value' => 'AQ',
        ],
        [
            'phoneCode' => '54',
            'value' => 'AR',
        ],
        [
            'phoneCode' => '1',
            'value' => 'AS',
        ],
        [
            'phoneCode' => '43',
            'value' => 'AT',
        ],
        [
            'phoneCode' => '61',
            'value' => 'AU',
        ],
        [
            'phoneCode' => '297',
            'value' => 'AW',
        ],
        [
            'phoneCode' => '358',
            'value' => 'AX',
        ],
        [
            'phoneCode' => '994',
            'value' => 'AZ',
        ],
        [
            'phoneCode' => '387',
            'value' => 'BA',
        ],
        [
            'phoneCode' => '1',
            'value' => 'BB',
        ],
        [
            'phoneCode' => '880',
            'value' => 'BD',
        ],
        [
            'phoneCode' => '32',
            'value' => 'BE',
        ],
        [
            'phoneCode' => '226',
            'value' => 'BF',
        ],
        [
            'phoneCode' => '359',
            'value' => 'BG',
        ],
        [
            'phoneCode' => '973',
            'value' => 'BH',
        ],
        [
            'phoneCode' => '257',
            'value' => 'BI',
        ],
        [
            'phoneCode' => '229',
            'value' => 'BJ',
        ],
        [
            'phoneCode' => '590',
            'value' => 'BL',
        ],
        [
            'phoneCode' => '1',
            'value' => 'BM',
        ],
        [
            'phoneCode' => '673',
            'value' => 'BN',
        ],
        [
            'phoneCode' => '591',
            'value' => 'BO',
        ],
        [
            'phoneCode' => '599',
            'value' => 'BQ',
        ],
        [
            'phoneCode' => '55',
            'value' => 'BR',
        ],
        [
            'phoneCode' => '1',
            'value' => 'BS',
        ],
        [
            'phoneCode' => '975',
            'value' => 'BT',
        ],
        [
            'phoneCode' => '47',
            'value' => 'BV',
        ],
        [
            'phoneCode' => '267',
            'value' => 'BW',
        ],
        [
            'phoneCode' => '375',
            'value' => 'BY',
        ],
        [
            'phoneCode' => '501',
            'value' => 'BZ',
        ],
        [
            'phoneCode' => '1',
            'value' => 'CA',
        ],
        [
            'phoneCode' => '61',
            'value' => 'CC',
        ],
        [
            'phoneCode' => '243',
            'value' => 'CD',
        ],
        [
            'phoneCode' => '236',
            'value' => 'CF',
        ],
        [
            'phoneCode' => '242',
            'value' => 'CG',
        ],
        [
            'phoneCode' => '41',
            'value' => 'CH',
        ],
        [
            'phoneCode' => '225',
            'value' => 'CI',
        ],
        [
            'phoneCode' => '682',
            'value' => 'CK',
        ],
        [
            'phoneCode' => '56',
            'value' => 'CL',
        ],
        [
            'phoneCode' => '237',
            'value' => 'CM',
        ],
        [
            'phoneCode' => '86',
            'value' => 'CN',
        ],
        [
            'phoneCode' => '57',
            'value' => 'CO',
        ],
        [
            'phoneCode' => '506',
            'value' => 'CR',
        ],
        [
            'phoneCode' => '53',
            'value' => 'CU',
        ],
        [
            'phoneCode' => '238',
            'value' => 'CV',
        ],
        [
            'phoneCode' => '599',
            'value' => 'CW',
        ],
        [
            'phoneCode' => '61',
            'value' => 'CX',
        ],
        [
            'phoneCode' => '357',
            'value' => 'CY',
        ],
        [
            'phoneCode' => '420',
            'value' => 'CZ',
        ],
        [
            'phoneCode' => '49',
            'value' => 'DE',
        ],
        [
            'phoneCode' => '253',
            'value' => 'DJ',
        ],
        [
            'phoneCode' => '45',
            'value' => 'DK',
        ],
        [
            'phoneCode' => '1',
            'value' => 'DM',
        ],
        [
            'phoneCode' => '1',
            'value' => 'DO',
        ],
        [
            'phoneCode' => '213',
            'value' => 'DZ',
        ],
        [
            'phoneCode' => '593',
            'value' => 'EC',
        ],
        [
            'phoneCode' => '372',
            'value' => 'EE',
        ],
        [
            'phoneCode' => '20',
            'value' => 'EG',
        ],
        [
            'phoneCode' => '212',
            'value' => 'EH',
        ],
        [
            'phoneCode' => '291',
            'value' => 'ER',
        ],
        [
            'phoneCode' => '34',
            'value' => 'ES',
        ],
        [
            'phoneCode' => '251',
            'value' => 'ET',
        ],
        [
            'phoneCode' => '358',
            'value' => 'FI',
        ],
        [
            'phoneCode' => '679',
            'value' => 'FJ',
        ],
        [
            'phoneCode' => '500',
            'value' => 'FK',
        ],
        [
            'phoneCode' => '691',
            'value' => 'FM',
        ],
        [
            'phoneCode' => '298',
            'value' => 'FO',
        ],
        [
            'phoneCode' => '33',
            'value' => 'FR',
        ],
        [
            'phoneCode' => '241',
            'value' => 'GA',
        ],
        [
            'phoneCode' => '44',
            'value' => 'GB',
        ],
        [
            'phoneCode' => '1',
            'value' => 'GD',
        ],
        [
            'phoneCode' => '995',
            'value' => 'GE',
        ],
        [
            'phoneCode' => '594',
            'value' => 'GF',
        ],
        [
            'phoneCode' => '44',
            'value' => 'GG',
        ],
        [
            'phoneCode' => '233',
            'value' => 'GH',
        ],
        [
            'phoneCode' => '350',
            'value' => 'GI',
        ],
        [
            'phoneCode' => '299',
            'value' => 'GL',
        ],
        [
            'phoneCode' => '220',
            'value' => 'GM',
        ],
        [
            'phoneCode' => '224',
            'value' => 'GN',
        ],
        [
            'phoneCode' => '590',
            'value' => 'GP',
        ],
        [
            'phoneCode' => '240',
            'value' => 'GQ',
        ],
        [
            'phoneCode' => '30',
            'value' => 'GR',
        ],
        [
            'phoneCode' => '500',
            'value' => 'GS',
        ],
        [
            'phoneCode' => '502',
            'value' => 'GT',
        ],
        [
            'phoneCode' => '1',
            'value' => 'GU',
        ],
        [
            'phoneCode' => '245',
            'value' => 'GW',
        ],
        [
            'phoneCode' => '592',
            'value' => 'GY',
        ],
        [
            'phoneCode' => '852',
            'value' => 'HK',
        ],
        [
            'phoneCode' => '672',
            'value' => 'HM',
        ],
        [
            'phoneCode' => '504',
            'value' => 'HN',
        ],
        [
            'phoneCode' => '385',
            'value' => 'HR',
        ],
        [
            'phoneCode' => '509',
            'value' => 'HT',
        ],
        [
            'phoneCode' => '36',
            'value' => 'HU',
        ],
        [
            'phoneCode' => '62',
            'value' => 'ID',
        ],
        [
            'phoneCode' => '353',
            'value' => 'IE',
        ],
        [
            'phoneCode' => '972',
            'value' => 'IL',
        ],
        [
            'phoneCode' => '44',
            'value' => 'IM',
        ],
        [
            'phoneCode' => '91',
            'value' => 'IN',
        ],
        [
            'phoneCode' => '246',
            'value' => 'IO',
        ],
        [
            'phoneCode' => '964',
            'value' => 'IQ',
        ],
        [
            'phoneCode' => '98',
            'value' => 'IR',
        ],
        [
            'phoneCode' => '354',
            'value' => 'IS',
        ],
        [
            'phoneCode' => '39',
            'value' => 'IT',
        ],
        [
            'phoneCode' => '44',
            'value' => 'JE',
        ],
        [
            'phoneCode' => '1',
            'value' => 'JM',
        ],
        [
            'phoneCode' => '962',
            'value' => 'JO',
        ],
        [
            'phoneCode' => '81',
            'value' => 'JP',
        ],
        [
            'phoneCode' => '254',
            'value' => 'KE',
        ],
        [
            'phoneCode' => '996',
            'value' => 'KG',
        ],
        [
            'phoneCode' => '855',
            'value' => 'KH',
        ],
        [
            'phoneCode' => '686',
            'value' => 'KI',
        ],
        [
            'phoneCode' => '269',
            'value' => 'KM',
        ],
        [
            'phoneCode' => '1',
            'value' => 'KN',
        ],
        [
            'phoneCode' => '850',
            'value' => 'KP',
        ],
        [
            'phoneCode' => '82',
            'value' => 'KR',
        ],
        [
            'phoneCode' => '965',
            'value' => 'KW',
        ],
        [
            'phoneCode' => '1',
            'value' => 'KY',
        ],
        [
            'phoneCode' => '76',
            'value' => 'KZ',
        ],
        [
            'phoneCode' => '856',
            'value' => 'LA',
        ],
        [
            'phoneCode' => '961',
            'value' => 'LB',
        ],
        [
            'phoneCode' => '1',
            'value' => 'LC',
        ],
        [
            'phoneCode' => '423',
            'value' => 'LI',
        ],
        [
            'phoneCode' => '94',
            'value' => 'LK',
        ],
        [
            'phoneCode' => '231',
            'value' => 'LR',
        ],
        [
            'phoneCode' => '266',
            'value' => 'LS',
        ],
        [
            'phoneCode' => '370',
            'value' => 'LT',
        ],
        [
            'phoneCode' => '352',
            'value' => 'LU',
        ],
        [
            'phoneCode' => '371',
            'value' => 'LV',
        ],
        [
            'phoneCode' => '218',
            'value' => 'LY',
        ],
        [
            'phoneCode' => '212',
            'value' => 'MA',
        ],
        [
            'phoneCode' => '377',
            'value' => 'MC',
        ],
        [
            'phoneCode' => '373',
            'value' => 'MD',
        ],
        [
            'phoneCode' => '382',
            'value' => 'ME',
        ],
        [
            'phoneCode' => '590',
            'value' => 'MF',
        ],
        [
            'phoneCode' => '261',
            'value' => 'MG',
        ],
        [
            'phoneCode' => '692',
            'value' => 'MH',
        ],
        [
            'phoneCode' => '389',
            'value' => 'MK',
        ],
        [
            'phoneCode' => '223',
            'value' => 'ML',
        ],
        [
            'phoneCode' => '95',
            'value' => 'MM',
        ],
        [
            'phoneCode' => '976',
            'value' => 'MN',
        ],
        [
            'phoneCode' => '853',
            'value' => 'MO',
        ],
        [
            'phoneCode' => '1',
            'value' => 'MP',
        ],
        [
            'phoneCode' => '596',
            'value' => 'MQ',
        ],
        [
            'phoneCode' => '222',
            'value' => 'MR',
        ],
        [
            'phoneCode' => '1',
            'value' => 'MS',
        ],
        [
            'phoneCode' => '356',
            'value' => 'MT',
        ],
        [
            'phoneCode' => '230',
            'value' => 'MU',
        ],
        [
            'phoneCode' => '960',
            'value' => 'MV',
        ],
        [
            'phoneCode' => '265',
            'value' => 'MW',
        ],
        [
            'phoneCode' => '52',
            'value' => 'MX',
        ],
        [
            'phoneCode' => '60',
            'value' => 'MY',
        ],
        [
            'phoneCode' => '258',
            'value' => 'MZ',
        ],
        [
            'phoneCode' => '264',
            'value' => 'NA',
        ],
        [
            'phoneCode' => '687',
            'value' => 'NC',
        ],
        [
            'phoneCode' => '227',
            'value' => 'NE',
        ],
        [
            'phoneCode' => '672',
            'value' => 'NF',
        ],
        [
            'phoneCode' => '234',
            'value' => 'NG',
        ],
        [
            'phoneCode' => '505',
            'value' => 'NI',
        ],
        [
            'phoneCode' => '31',
            'value' => 'NL',
        ],
        [
            'phoneCode' => '47',
            'value' => 'NO',
        ],
        [
            'phoneCode' => '977',
            'value' => 'NP',
        ],
        [
            'phoneCode' => '674',
            'value' => 'NR',
        ],
        [
            'phoneCode' => '683',
            'value' => 'NU',
        ],
        [
            'phoneCode' => '64',
            'value' => 'NZ',
        ],
        [
            'phoneCode' => '968',
            'value' => 'OM',
        ],
        [
            'phoneCode' => '507',
            'value' => 'PA',
        ],
        [
            'phoneCode' => '51',
            'value' => 'PE',
        ],
        [
            'phoneCode' => '689',
            'value' => 'PF',
        ],
        [
            'phoneCode' => '675',
            'value' => 'PG',
        ],
        [
            'phoneCode' => '63',
            'value' => 'PH',
        ],
        [
            'phoneCode' => '92',
            'value' => 'PK',
        ],
        [
            'phoneCode' => '48',
            'value' => 'PL',
        ],
        [
            'phoneCode' => '508',
            'value' => 'PM',
        ],
        [
            'phoneCode' => '64',
            'value' => 'PN',
        ],
        [
            'phoneCode' => '970',
            'value' => 'PS',
        ],
        [
            'phoneCode' => '351',
            'value' => 'PT',
        ],
        [
            'phoneCode' => '680',
            'value' => 'PW',
        ],
        [
            'phoneCode' => '595',
            'value' => 'PY',
        ],
        [
            'phoneCode' => '974',
            'value' => 'QA',
        ],
        [
            'phoneCode' => '262',
            'value' => 'RE',
        ],
        [
            'phoneCode' => '40',
            'value' => 'RO',
        ],
        [
            'phoneCode' => '381',
            'value' => 'RS',
        ],
        [
            'phoneCode' => '7',
            'value' => 'RU',
        ],
        [
            'phoneCode' => '250',
            'value' => 'RW',
        ],
        [
            'phoneCode' => '966',
            'value' => 'SA',
        ],
        [
            'phoneCode' => '677',
            'value' => 'SB',
        ],
        [
            'phoneCode' => '248',
            'value' => 'SC',
        ],
        [
            'phoneCode' => '249',
            'value' => 'SD',
        ],
        [
            'phoneCode' => '46',
            'value' => 'SE',
        ],
        [
            'phoneCode' => '65',
            'value' => 'SG',
        ],
        [
            'phoneCode' => '290',
            'value' => 'SH',
        ],
        [
            'phoneCode' => '386',
            'value' => 'SI',
        ],
        [
            'phoneCode' => '47',
            'value' => 'SJ',
        ],
        [
            'phoneCode' => '421',
            'value' => 'SK',
        ],
        [
            'phoneCode' => '232',
            'value' => 'SL',
        ],
        [
            'phoneCode' => '378',
            'value' => 'SM',
        ],
        [
            'phoneCode' => '221',
            'value' => 'SN',
        ],
        [
            'phoneCode' => '252',
            'value' => 'SO',
        ],
        [
            'phoneCode' => '597',
            'value' => 'SR',
        ],
        [
            'phoneCode' => '239',
            'value' => 'ST',
        ],
        [
            'phoneCode' => '503',
            'value' => 'SV',
        ],
        [
            'phoneCode' => '1',
            'value' => 'SX',
        ],
        [
            'phoneCode' => '963',
            'value' => 'SY',
        ],
        [
            'phoneCode' => '268',
            'value' => 'SZ',
        ],
        [
            'phoneCode' => '1',
            'value' => 'TC',
        ],
        [
            'phoneCode' => '235',
            'value' => 'TD',
        ],
        [
            'phoneCode' => '262',
            'value' => 'TF',
        ],
        [
            'phoneCode' => '228',
            'value' => 'TG',
        ],
        [
            'phoneCode' => '66',
            'value' => 'TH',
        ],
        [
            'phoneCode' => '992',
            'value' => 'TJ',
        ],
        [
            'phoneCode' => '690',
            'value' => 'TK',
        ],
        [
            'phoneCode' => '670',
            'value' => 'TL',
        ],
        [
            'phoneCode' => '993',
            'value' => 'TM',
        ],
        [
            'phoneCode' => '216',
            'value' => 'TN',
        ],
        [
            'phoneCode' => '676',
            'value' => 'TO',
        ],
        [
            'phoneCode' => '90',
            'value' => 'TR',
        ],
        [
            'phoneCode' => '1',
            'value' => 'TT',
        ],
        [
            'phoneCode' => '688',
            'value' => 'TV',
        ],
        [
            'phoneCode' => '886',
            'value' => 'TW',
        ],
        [
            'phoneCode' => '255',
            'value' => 'TZ',
        ],
        [
            'phoneCode' => '380',
            'value' => 'UA',
        ],
        [
            'phoneCode' => '256',
            'value' => 'UG',
        ],
        [
            'phoneCode' => '246',
            'value' => 'UM',
        ],
        [
            'phoneCode' => '1',
            'value' => 'US',
        ],
        [
            'phoneCode' => '598',
            'value' => 'UY',
        ],
        [
            'phoneCode' => '998',
            'value' => 'UZ',
        ],
        [
            'phoneCode' => '379',
            'value' => 'VA',
        ],
        [
            'phoneCode' => '1',
            'value' => 'VC',
        ],
        [
            'phoneCode' => '58',
            'value' => 'VE',
        ],
        [
            'phoneCode' => '1',
            'value' => 'VG',
        ],
        [
            'phoneCode' => '1 340',
            'value' => 'VI',
        ],
        [
            'phoneCode' => '84',
            'value' => 'VN',
        ],
        [
            'phoneCode' => '678',
            'value' => 'VU',
        ],
        [
            'phoneCode' => '681',
            'value' => 'WF',
        ],
        [
            'phoneCode' => '685',
            'value' => 'WS',
        ],
        [
            'phoneCode' => '383',
            'value' => 'XK',
        ],
        [
            'phoneCode' => '967',
            'value' => 'YE',
        ],
        [
            'phoneCode' => '262',
            'value' => 'YT',
        ],
        [
            'phoneCode' => '27',
            'value' => 'ZA',
        ],
        [
            'phoneCode' => '260',
            'value' => 'ZM',
        ],
        [
            'phoneCode' => '263',
            'value' => 'ZW',
        ],
    ];

    private const CACHE_TAG = 'MAGEBIT_COUNTRY_LIST';

    /**
     * @param CollectionFactory $collectionFactory
     * @param SvgIcons $icons
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly SvgIcons $icons,
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer,
    ) {
    }

    /**
     * Get array of countries with phone codes
     *
     * @return array
     */
    public function getCountryList(): array
    {
        $cacheKey = self::CACHE_TAG;

        if ($result = $this->cache->load($cacheKey)) {
            return $this->serializer->unserialize($result);
        }

        $collection = $this->collectionFactory->create();

        $countries = array_filter($collection->toOptionArray(), function ($item) {
            return !empty($item['value']) && trim($item['label']) !== "";
        });

        $phoneCodesIndexedByValue = [];
        foreach (self::COUNTRY_PHONE_CODES as $item) {
            $phoneCodesIndexedByValue[$item['value']] = $item;
        }

        $result = array_map(function ($country) use ($phoneCodesIndexedByValue) {
            $value = $country['value'];
            if (isset($phoneCodesIndexedByValue[$value])) {
                return array_merge($country, $phoneCodesIndexedByValue[$value]);
            }

            return $country;
        }, $countries);

        foreach ($result as &$item) {
            if (isset($item['value'])) {
                $item['icon'] = $this->icons->renderHtml(
                    'flags/' . strtolower($item['value']),
                    '',
                    20,
                    20
                );
            }
        }

        $this->cache->save($this->serializer->serialize($result), $cacheKey, [self::CACHE_TAG]);

        return $result;
    }
}
