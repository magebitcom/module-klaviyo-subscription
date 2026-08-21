<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Model\Api;

use Klaviyo\Reclaim\KlaviyoV3Sdk\KlaviyoV3Api;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\CurlFactory;

/**
 * The two Klaviyo profile endpoints the bundled SDK does not expose.
 *
 * `Klaviyo\Reclaim\KlaviyoV3Sdk\KlaviyoV3Api` offers no upsert-by-email at all — its
 * `createProfile` fails on an existing profile and it is instantiated with `new` inside
 * `Klaviyo\Reclaim\Helper\Data`, so it cannot be extended through DI either. Its API revision
 * constant is reused so we always speak the same revision as the installed extension.
 */
class ProfileClient
{
    /**
     * Upsert a single profile by email
     */
    private const ENDPOINT_PROFILE_IMPORT = KlaviyoV3Api::KLAVIYO_HOST . 'api/profile-import';

    /**
     * Upsert up to 10,000 profiles asynchronously
     */
    private const ENDPOINT_BULK_IMPORT = KlaviyoV3Api::KLAVIYO_HOST . 'api/profile-bulk-import-jobs';

    /**
     * Klaviyo's own cap for a single bulk import job
     */
    public const BULK_IMPORT_LIMIT = 10000;

    /**
     * Kept short: the single-profile call runs inside a customer or subscriber save
     */
    private const TIMEOUT_SECONDS = 5;

    /**
     * @param CurlFactory $curlFactory
     */
    public function __construct(
        private readonly CurlFactory $curlFactory
    ) {
    }

    /**
     * Create or update one profile, matched on email.
     *
     * `properties` is merged into any custom properties the profile already has, so this never
     * clears data written by Klaviyo forms or by the extension.
     *
     * @param string $apiKey
     * @param array<string, mixed> $attributes
     * @return void
     * @throws LocalizedException
     */
    public function importProfile(string $apiKey, array $attributes): void
    {
        $this->post($apiKey, self::ENDPOINT_PROFILE_IMPORT, [
            'data' => [
                'type' => 'profile',
                'attributes' => $attributes,
            ],
        ]);
    }

    /**
     * Queue a bulk profile import job. Klaviyo processes it asynchronously and answers 202.
     *
     * @param string $apiKey
     * @param array<int, array<string, mixed>> $profiles
     * @return void
     * @throws LocalizedException
     */
    public function bulkImportProfiles(string $apiKey, array $profiles): void
    {
        if ($profiles === []) {
            return;
        }

        if (count($profiles) > self::BULK_IMPORT_LIMIT) {
            throw new LocalizedException(
                __('A Klaviyo bulk import job accepts at most %1 profiles.', self::BULK_IMPORT_LIMIT)
            );
        }

        $this->post($apiKey, self::ENDPOINT_BULK_IMPORT, [
            'data' => [
                'type' => 'profile-bulk-import-job',
                'attributes' => [
                    'profiles' => [
                        'data' => array_map(
                            static fn (array $attributes): array => [
                                'type' => 'profile',
                                'attributes' => $attributes,
                            ],
                            array_values($profiles)
                        ),
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param string $apiKey
     * @param string $url
     * @param array<string, mixed> $body
     * @return void
     * @throws LocalizedException
     */
    private function post(string $apiKey, string $url, array $body): void
    {
        try {
            $payload = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $e) {
            throw new LocalizedException(__('Could not encode the Klaviyo payload: %1', $e->getMessage()), $e);
        }

        $curl = $this->curlFactory->create();
        $curl->setTimeout(self::TIMEOUT_SECONDS);
        $curl->addHeader('Authorization', 'Klaviyo-API-Key ' . $apiKey);
        $curl->addHeader('revision', KlaviyoV3Api::KLAVIYO_V3_REVISION);
        $curl->addHeader('Content-Type', 'application/json');
        $curl->addHeader('accept', 'application/json');

        try {
            $curl->post($url, $payload);
            $status = $curl->getStatus();
            $responseBody = $curl->getBody();
        } catch (\Exception $e) {
            throw new LocalizedException(__('Klaviyo request to %1 failed: %2', $url, $e->getMessage()), $e);
        }

        if ($status < 200 || $status >= 300) {
            throw new LocalizedException(
                __('Klaviyo answered HTTP %1 for %2: %3', $status, $url, $responseBody)
            );
        }
    }
}
