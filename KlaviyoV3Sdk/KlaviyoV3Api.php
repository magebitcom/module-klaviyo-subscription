<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\KlaviyoV3Sdk;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\KlaviyoV3Sdk\KlaviyoV3Api as Origin;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;

class KlaviyoV3Api extends Origin
{
    /**
     * Constants for subscription consent and email attribute
     */
    public const SUBSCRIBE = 'SUBSCRIBED';
    public const UNSUBSCRIBE = 'UNSUBSCRIBED';
    public const EMAIL_KEY = 'email';
    public const TELEPHONE_KEY = 'phone_number';

    public const  SUBSCRIPTION_KEY = 'subscriptions';
    public const  SMS_KEY = 'sms';
    public const CONSENT_KEY = 'consent';
    public const MARKETING_KEY = 'marketing';

    public function __construct(
        $public_key,
        $private_key,
        private readonly ScopeSetting $klaviyoScopeSetting
    ) {
        parent::__construct(
            $public_key,
            $private_key,
            $klaviyoScopeSetting
        );
    }

    /**
     * Unsubscribe members from Klaviyo SMS list
     *
     * @param string $email
     * @return array|null|string
     */
    public function unsubscribeSmSFromKlaviyoList(string $email, string $phoneNumber): array|string|null
    {
        // @codingStandardsIgnoreFile
        $body = array(
            self::DATA_KEY_PAYLOAD => array(
                self::TYPE_KEY_PAYLOAD => self::PROFILE_SUBSCRIPTION_BULK_DELETE_JOB_PAYLOAD_KEY,
                self::ATTRIBUTE_KEY_PAYLOAD => array(
                    self::PROFILES_PAYLOAD_KEY => array(
                        self::DATA_KEY_PAYLOAD => array(
                            array(
                                self::TYPE_KEY_PAYLOAD => self::PROFILE_KEY_PAYLOAD,
                                self::ATTRIBUTE_KEY_PAYLOAD => array(
                                    self::EMAIL_KEY => $email,
                                    self::TELEPHONE_KEY => $phoneNumber,
                                    self::SUBSCRIPTION_KEY => array(
                                        self::SMS_KEY => array(
                                            self::MARKETING_KEY => array(
                                                self::CONSENT_KEY => self::UNSUBSCRIBE
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );

        return $this->requestV3('/api/profile-subscription-bulk-delete-jobs/', self::HTTP_POST, $body);
    }

    /**
     * Subscribe members to a Klaviyo SMS list
     *
     * @param string $email
     * @param string $phoneNumber
     * @param string $listId
     * @return array
     */
    public function subscribeSmSToKlaviyoList(string $email, string $phoneNumber, string $listId): array
    {
        $body = [
            self::DATA_KEY_PAYLOAD => [
                self::TYPE_KEY_PAYLOAD => self::PROFILE_SUBSCRIPTION_BULK_CREATE_JOB_PAYLOAD_KEY,
                self::ATTRIBUTE_KEY_PAYLOAD => [
                    self::CUSTOM_SOURCE_PAYLOAD_KEY => self::MAGENTO_TWO_PAYLOAD_VALUE,
                    self::PROFILES_PAYLOAD_KEY => [
                        self::DATA_KEY_PAYLOAD => [
                            [
                                self::TYPE_KEY_PAYLOAD => self::PROFILE_KEY_PAYLOAD,
                                self::ATTRIBUTE_KEY_PAYLOAD => [
                                    self::EMAIL_KEY => $email,
                                    self::TELEPHONE_KEY => $phoneNumber,
                                    self::SUBSCRIPTION_KEY => [
                                        self::SMS_KEY => [
                                            self::MARKETING_KEY => [
                                                self::CONSENT_KEY => self::SUBSCRIBE
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                self::RELATIONSHIPS_PAYLOAD_KEY => [
                    self::LIST_PAYLOAD_KEY => [
                        self::DATA_KEY_PAYLOAD => [
                            self::TYPE_KEY_PAYLOAD => self::LIST_PAYLOAD_KEY,
                            self::ID_KEY_PAYLOAD => $listId
                        ]
                    ]
                ]
            ]
        ];

        return $this->requestV3('/api/profile-subscription-bulk-create-jobs/', self::HTTP_POST, $body);
    }

    /**
     * Build headers for the Klaviyo all event
     *
     * @return array|array[]
     */
    public function getHeaders(): array
    {
        $klVersion = $this->klaviyoScopeSetting->getVersion();

        $objectManager = ObjectManager::getInstance();
        $productMetadata = $objectManager->get(ProductMetadataInterface::class);
        $m2Version = $productMetadata->getVersion();

        return array(
            CURLOPT_HTTPHEADER => [
                self::REVISION_KEY_HEADER . ': ' . self::KLAVIYO_V3_REVISION,
                self::CONTENT_TYPE_KEY_HEADER . ': ' . self::APPLICATION_JSON_HEADER_VALUE,
                self::ACCEPT_KEY_HEADER . ': ' . self::APPLICATION_JSON_HEADER_VALUE,
                self::KLAVIYO_USER_AGENT_KEY . ': ' . 'magento2-klaviyo/' . $klVersion . ' Magento2/' . $m2Version . ' PHP/' . phpversion(),
                self::AUTHORIZATION_KEY_HEADER . ': ' . self::KLAVIYO_API_KEY . ' ' . $this->private_key
            ]
        );
    }

    /**
     * Search for profile by Email
     *
     * @param $email
     * @return array|bool
     */
    public function searchProfileByEmail($email): array|bool
    {
        $encoded_email = urlencode($email);
        $response_body = $this->requestV3(
            "api/profiles/?filter=equals(email,'$encoded_email')&additional-fields[profile]=subscriptions",
            self::HTTP_GET
        );

        if (empty($response_body[self::DATA_KEY_PAYLOAD])) {

            return false;
        }

        $id = $response_body[self::DATA_KEY_PAYLOAD][0][self::ID_KEY_PAYLOAD];

        return [
            'response' => $response_body,
            'profile_id' => $id
        ];
    }
}
