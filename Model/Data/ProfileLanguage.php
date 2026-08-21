<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Model\Data;

/**
 * The two representations of a store view's language that Klaviyo understands.
 *
 * `locale` is Klaviyo's native profile attribute (IETF BCP 47, e.g. lv-LV); `language` is the
 * short code (lv) written to the custom `Language` profile property the flow splits evaluate.
 */
class ProfileLanguage
{
    /**
     * @param string $locale
     * @param string $language
     */
    public function __construct(
        private readonly string $locale,
        private readonly string $language
    ) {
    }

    /**
     * Klaviyo native `locale` attribute value, e.g. lv-LV
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Short language code written to the custom `Language` property, e.g. lv
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }
}
