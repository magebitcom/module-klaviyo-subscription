<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Api;

/**
 * Validates phone numbers for Klaviyo SMS against admin-configured Klaviyo SMS regions.
 */
interface SmsPhoneValidationInterface
{
    /**
     * Whether the number is valid for at least one enabled region (store scope).
     *
     * @param string $phoneNumber Raw input (may include formatting).
     */
    public function isValidForConfiguredRegions(string $phoneNumber): bool;

    /**
     * Returns E.164-style value with leading + or null when invalid / no regions enabled.
     *
     * @param string $phoneNumber Raw input (may include formatting).
     */
    public function getInternationalNumberOrNull(string $phoneNumber): ?string;
}
