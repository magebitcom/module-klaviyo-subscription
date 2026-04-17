<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Newsletter\Controller\Manage\Save;
use Magebit\KlaviyoSubscription\Api\SmsPhoneValidationInterface;
use Magebit\KlaviyoSubscription\Helper\Data;

/**
 * Plugin to subscribe customer to Klaviyo after managing subscriptions in dashboard
 */
class SubscribeFromCustomerDashboard
{
    /**
     * @param Session $customerSession
     * @param CustomerRepository $customerRepository
     * @param Data $helper
     * @param SmsPhoneValidationInterface $smsPhoneValidation
     */
    public function __construct(
        private readonly Session $customerSession,
        private readonly CustomerRepository $customerRepository,
        private readonly Data $helper,
        private readonly SmsPhoneValidationInterface $smsPhoneValidation,
    ) {
    }

    /**
     * @param Save $subject
     * @return void
     */
    public function beforeExecute(Save $subject): void
    {
        $isSmsSubscribedParam = (bool)$subject->getRequest()->getParam('is_sms_subscribed', false);

        if ($subject->getRequest()->getParam('is_sms_subscribed') == null) {
            return;
        }

        $customerId = $this->customerSession->getCustomerId();
        /** @var Customer $customer */
        $customer = $this->customerRepository->getById($customerId);

        $email = $customer->getEmail();

        $isSmsSubscribedState = $this->helper->isCustomerSubscribed($email);

        // Only proceed if the subscription state has changed
        if ($isSmsSubscribedState !== $isSmsSubscribedParam) {
            if (!$isSmsSubscribedState && $isSmsSubscribedParam) {
                // Going from unsubscribed -> subscribed
                // Get phone and country code from POST
                $telephone = $subject->getRequest()->getParam('telephone', false);

                if (!$telephone) {
                    return;
                }

                $internationalPhone = $this->smsPhoneValidation->getInternationalNumberOrNull($telephone);

                if ($internationalPhone === null) {
                    return;
                }

                $this->helper->subscribeSmSToKlaviyoList($customer->getEmail(), $internationalPhone);

            } elseif ($isSmsSubscribedState && !$isSmsSubscribedParam) {
                // Going from subscribed -> unsubscribed

                $this->helper->unsubscribeSmSFromKlaviyoList($email);

            }
        }
    }
}
