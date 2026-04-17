<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Plugin;

use Magebit\KlaviyoSubscription\Api\SmsPhoneValidationInterface;
use Magebit\KlaviyoSubscription\Helper\Data;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\SubscriptionManager;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Plugin to handle customer save after action
 */
class CustomerSavePlugin
{
    /**
     * @param RequestInterface $request
     * @param Data $helper
     * @param AddressRepositoryInterface $addressRepository
     * @param SubscriptionManager $subscriptionManager
     * @param StoreManagerInterface $storeManager
     * @param SmsPhoneValidationInterface $smsPhoneValidation
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly Data $helper,
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly SubscriptionManager $subscriptionManager,
        private readonly StoreManagerInterface $storeManager,
        private readonly SmsPhoneValidationInterface $smsPhoneValidation
    ) {
    }

    /**
     * After save plugin to grab request params and process customer data
     *
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface $result
     * @param CustomerInterface $customer
     * @return CustomerInterface
     */
    public function afterSave(
        CustomerRepositoryInterface $subject,
        CustomerInterface $result,
        CustomerInterface $customer
    ): CustomerInterface {
        // Get all request parameters (includes GET, POST, etc.)
        $requestParams = $this->request->getParams();

        if (!isset($requestParams['customer'])) {
            return $result;
        }

        // Use $result instead of $customer to ensure we have the saved customer with ID
        $isSmsSubscribed = (bool) $this->request->getParams()['customer']['is_sms_subscribed'];
        $this->manageSmsSubscription($result, $isSmsSubscribed);
        $isNewsletterSubscribed = (bool) $this->request->getParams()['customer']['newsletter_subscription'];
        $this->manageEmailSubscription($result, $isNewsletterSubscribed);

        return $result;
    }

    /**
     * Manage sms subscription for the customer
     *
     * @param CustomerInterface $customer
     * @param bool $isSmsSubscribed
     * @return void
     * @throws LocalizedException
     */
    private function manageSmsSubscription(CustomerInterface $customer, bool $isSmsSubscribed): void
    {
        $email = $customer->getEmail();
        $isSmsSubscribedState = $this->helper->isCustomerSubscribed($email);

        if ($isSmsSubscribedState !== $isSmsSubscribed) {
            if (!$isSmsSubscribedState && $isSmsSubscribed) {
                $addressId = $customer->getDefaultBilling() ?: $customer->getDefaultShipping();
                try {
                    $address = $this->addressRepository->getById($addressId);
                } catch (NoSuchEntityException $e) {
                    return;
                }

                $phoneNumber = $address->getTelephone() ?? '';

                if (!$phoneNumber) {
                    return;
                }

                $internationalPhone = $this->smsPhoneValidation->getInternationalNumberOrNull($phoneNumber);

                if ($internationalPhone === null) {
                    return;
                }

                $this->helper->subscribeSmSToKlaviyoList($customer->getEmail(), $internationalPhone);
            } elseif ($isSmsSubscribedState && !$isSmsSubscribed) {
                $this->helper->unsubscribeSmSFromKlaviyoList($email);
            }
        }
    }

    /**
     * Manage email subscription for the customer
     *
     * @param CustomerInterface $customer
     * @param bool $isEmailSubscribed
     * @return void
     * @throws NoSuchEntityException
     */
    private function manageEmailSubscription(CustomerInterface $customer, bool $isEmailSubscribed): void
    {
        $customerId = $customer->getId();

        if (!$customerId) {
            return;
        }

        $storeId = (int) $this->storeManager->getStore()->getId();

        if ($isEmailSubscribed) {
            $this->subscriptionManager->subscribeCustomer((int) $customerId, $storeId);

            return;
        }

        $this->subscriptionManager->unsubscribeCustomer((int) $customerId, $storeId);
    }
}
