<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Helper;

use Klaviyo\Reclaim\Helper\Data as Origin;
use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magebit\KlaviyoSubscription\KlaviyoV3Sdk\KlaviyoV3Api;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;

class Data extends Origin
{
    /**
     * @param Context $context
     * @param Logger $klaviyoLogger
     * @param ScopeSetting $klaviyoScopeSetting
     * @param Json $json
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        Context $context,
        Logger $klaviyoLogger,
        ScopeSetting $klaviyoScopeSetting,
        private readonly Json $json,
        private readonly CustomerRepository $customerRepository
    ) {
        parent::__construct(
            $context,
            $klaviyoLogger,
            $klaviyoScopeSetting
        );
    }

    /**
     * @param string $email
     * @return bool|array|string|null
     */
    public function unsubscribeSmSFromKlaviyoList(string $email): bool|array|string|null
    {
        $api = new KlaviyoV3Api(
            $this->_klaviyoScopeSetting->getPublicApiKey(),
            $this->_klaviyoScopeSetting->getPrivateApiKey(),
            $this->_klaviyoScopeSetting
        );
        $user = $api->searchProfileByEmail($email);
        if (!isset($user['response']['data'][0]['attributes']['phone_number'])) {
            $this->_klaviyoLogger->log(
                sprintf('Unable to unsubscribe, phone number is missing:  %s ', $this->json->serialize($user))
            );

            return false;
        }
        $phoneNumber = $user['response']['data'][0]['attributes']['phone_number'];
        try {
            $response = $api->unsubscribeSmSFromKlaviyoList($email, $phoneNumber);
        } catch (\Exception $e) {
            $this->_klaviyoLogger->log(sprintf('Unable to unsubscribe %s: %s', $email, $e));
            $response = false;
        }

        return $response;
    }

    /**
     * @param string $email
     * @param string $telephone
     * @return array|false|null|string
     */
    public function subscribeSmSToKlaviyoList(string $email, string $telephone): bool|array|string|null
    {
        $api = new KlaviyoV3Api(
            $this->_klaviyoScopeSetting->getPublicApiKey(),
            $this->_klaviyoScopeSetting->getPrivateApiKey(),
            $this->_klaviyoScopeSetting
        );

        $listId = $this->_klaviyoScopeSetting->getConsentAtCheckoutSMSListId();

        try {
            $response = $api->subscribeSmSToKlaviyoList($email, $telephone, $listId);
        } catch (\Exception $e) {
            $this->_klaviyoLogger->log(sprintf('Unable to subscribe %s to list %s: %s', $email, $listId, $e));
            $response = false;
        }

        return $response;
    }

    /**
     * Save SMS subscription extension attribute on customer.
     *
     * @param string $email
     * @param bool $isSmsSubscribed
     * @return void
     */
    public function saveSmsSubscriptionAttribute(string $email, bool $isSmsSubscribed): void
    {
        try {
            $customer = $this->customerRepository->get($email);
        } catch (NoSuchEntityException $e) {
            return;
        }

        $customer->getExtensionAttributes()->setIsSmsSubscribed($isSmsSubscribed);
        $this->customerRepository->save($customer);
    }

    /**
     * Check if the customer is subscribed to SMS.
     *
     * @return bool
     */
    public function isCustomerSubscribed($email): bool
    {
        $api = new KlaviyoV3Api(
            $this->_klaviyoScopeSetting->getPublicApiKey(),
            $this->_klaviyoScopeSetting->getPrivateApiKey(),
            $this->_klaviyoScopeSetting
        );

        $user = $api->searchProfileByEmail($email);

        if (!isset($user['response']['data'][0]['attributes']['subscriptions']['sms']['marketing']['consent'])) {
            $this->_klaviyoLogger->log(
                sprintf('Unable to fetch, sms consent is missing: %s', $this->json->serialize($user))
            );

            return false;
        }

        $consent = $user['response']['data'][0]['attributes']['subscriptions']['sms']['marketing']['consent'];

        return $consent === KlaviyoV3Api::SUBSCRIBE;
    }
}
