<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\ViewModel;

use Exception;
use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magebit\KlaviyoSubscription\Helper\Data as KlaviyoHelper;
use Magebit\KlaviyoSubscription\KlaviyoV3Sdk\KlaviyoV3Api;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Block\Newsletter;
use Magento\Customer\Model\Session;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriptionManager;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Newsletter\Model\SubscriberFactory;

/**
 * ViewModel for customer subscriptions.
 */
class KlaviyoSubscriptionViewModel implements ArgumentInterface
{
    /**
     * @var array|bool
     */
    private $userInfo = false;

    /**
     * @param Session $customerSession
     * @param CustomerRepository $customerRepository
     * @param ScopeSetting $scopeSetting
     * @param Newsletter $newsletter
     * @param AddressRepositoryInterface $addressRepository
     * @param ScopeSetting $klaviyoScopeSetting
     * @param Logger $klaviyoLogger
     * @param Json $json
     * @param CheckoutSession $checkoutSession
     * @param SubscriptionManager $subscriptionManager
     * @param StoreManagerInterface $storeManager
     * @param SubscriberFactory $subscriberFactory
     * @param KlaviyoHelper $klaviyoHelper
     */
    public function __construct(
        private readonly Session $customerSession,
        private readonly CustomerRepository $customerRepository,
        private readonly ScopeSetting $scopeSetting,
        private readonly Newsletter $newsletter,
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly ScopeSetting $klaviyoScopeSetting,
        private readonly Logger $klaviyoLogger,
        private readonly Json $json,
        private readonly CheckoutSession $checkoutSession,
        private readonly SubscriptionManager $subscriptionManager,
        private readonly StoreManagerInterface $storeManager,
        private readonly SubscriberFactory $subscriberFactory,
        private readonly KlaviyoHelper $klaviyoHelper
    ) {
    }

    /**
     * Get customer data from the session.
     *
     * @return CustomerInterface|null
     */
    public function getCustomerData(): ?CustomerInterface
    {
        $customerId = $this->customerSession->getCustomerId();

        if (!$customerId) {
            return null;
        }

        return $this->customerRepository->getById($customerId);
    }

    /**
     * Get the customer's email address.
     *
     * @return string|null
     */
    public function getCustomerEmail(): ?string
    {
        if ($this->customerSession->isLoggedIn()) {
            $customerData = $this->getCustomerData();

            return $customerData ? $customerData->getEmail() : '';
        }

        $quote = $this->getQuote();

        if ($quote) {
            return $quote->getCustomerEmail();
        }

        return '';
    }

    /**
     * Retrieve quote object.
     *
     * @return Quote|null
     */
    private function getQuote(): ?Quote
    {
        try {
            return $this->checkoutSession->getQuote();
        } catch (Exception $e) {
            $this->klaviyoLogger->log('Unable to retrieve quote: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Check if the customer is subscribed to SMS.
     *
     * @return bool
     */
    public function isCustomerSmsSubscribed(): bool
    {
        $email = $this->getCustomerEmail();
        if (!$email) {
            return false;
        }

        $user = $this->searchProfileByEmail($email);

        if (!isset($user['response']['data'][0]['attributes']['subscriptions']['sms']['marketing']['consent'])) {
            $this->klaviyoLogger->log(
                sprintf('Unable to fetch, email number is missing: %s', $this->json->serialize($user))
            );

            $this->klaviyoHelper->saveSmsSubscriptionAttribute($email, false);

            return false;
        }

        $consent = $user['response']['data'][0]['attributes']['subscriptions']['sms']['marketing']['consent'];

        $isSmsSubscribed = $consent === KlaviyoV3Api::SUBSCRIBE;

        $this->klaviyoHelper->saveSmsSubscriptionAttribute($email, $isSmsSubscribed);

        return $isSmsSubscribed;
    }

    /**
     * Check if the customer is subscribed to the newsletter.
     *
     * @return bool
     */
    public function isCustomerEmailSubscribed(): bool
    {
        $email = $this->getCustomerEmail();
        if (!$email) {
            return false;
        }

        $user = $this->searchProfileByEmail($email);

        if (!isset($user['response']['data'][0]['attributes']['subscriptions']['email']['marketing']['consent'])) {
            $this->klaviyoLogger->log(
                sprintf('Unable to fetch, email number is missing: %s', $this->json->serialize($user))
            );

            return false;
        }

        $consent = $user['response']['data'][0]['attributes']['subscriptions']['email']['marketing']['consent'];

        $isKlaviyoSubscribed = $consent === KlaviyoV3Api::SUBSCRIBE;

        if ($isKlaviyoSubscribed) {
            $this->subscriptionManager->subscribe($email, (int) $this->storeManager->getStore()->getId());

            return true;
        }

        /**
         * @var Subscriber $subscriber
         */
        $subscriber = $this->subscriberFactory->create()->loadBySubscriberEmail(
            $email,
            (int) $this->storeManager->getWebsite()->getId()
        );

        if ($subscriber->isSubscribed()) {
            $code = $subscriber->getSubscriberConfirmCode();

            $this->subscriptionManager->unsubscribe($email, (int)$this->storeManager->getStore()->getId(), $code);
        }

        return $isKlaviyoSubscribed;
    }

    /**
     * Search profile by email
     *
     * @param string $email
     * @return array|bool
     */
    private function searchProfileByEmail(string $email): array|bool
    {
        if ($this->userInfo) {
            return $this->userInfo;
        }

        $api = new KlaviyoV3Api(
            $this->klaviyoScopeSetting->getPublicApiKey(),
            $this->klaviyoScopeSetting->getPrivateApiKey(),
            $this->klaviyoScopeSetting
        );

        $this->userInfo = $api->searchProfileByEmail($email);

        return $this->userInfo;
    }

    /**
     * Get label for subscribe to SMS checkbox.
     *
     * @return string
     */
    public function getCheckboxLabel(): string
    {
        return (string)$this->scopeSetting->getConsentAtCheckoutSMSConsentText();
    }

    /**
     * Get customer subscriptions.
     *
     * @return array
     */
    public function getCustomerSubscriptions(): array
    {
        $customerId = $this->customerSession->getCustomerId();

        if (!$customerId) {
            return [];
        }

        return [
            'general_subscription' => $this->newsletter->getIsSubscribed(),
            'sms_subscription' => $this->isCustomerSmsSubscribed()
        ];
    }

    /**
     * Check if Default Address has a foreign phone number and determine
     * if SMS checkbox needs to be seen, as SMS is only available for US, CA.
     *
     * @return bool
     */
    public function showSmSCheckbox(): bool
    {
        $customer = $this->getCustomerData();

        if ($this->isCustomerSmsSubscribed()) {
            return true;
        }

        if (!$customer) {
            return true;
        }

        $defaultShippingAddressId = $customer->getDefaultShipping();
        $defaultBillingAddressId = $customer->getDefaultBilling();

        if ($defaultShippingAddressId) {
            $address = $this->addressRepository->getById($defaultShippingAddressId);
        } elseif ($defaultBillingAddressId) {
            $address = $this->addressRepository->getById($defaultBillingAddressId);
        } else {

            return true;
        }

        if ($address && $address->getTelephone()) {
            $telephone = $address->getTelephone();
            return $this->validatePhoneNumber($telephone);
        }

        return false;
    }

    /**
     * Validate a phone number to ensure it meets specific criteria.
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function validatePhoneNumber(string $phoneNumber): bool
    {
        $sanitizedPhone = preg_replace('/\D/', '', $phoneNumber);

        return strlen($sanitizedPhone) === 11 && $sanitizedPhone[0] === '1';
    }

    /**
     * Check if klaviyo enabled
     *
     * @return bool
     */
    public function isKlaviyoEnabled(): bool
    {
        return (bool) $this->scopeSetting->isEnabled();
    }
}
