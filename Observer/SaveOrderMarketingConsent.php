<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Observer;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Webhook;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Klaviyo\Reclaim\Observer\SaveOrderMarketingConsent as CoreObserver;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magebit\KlaviyoSubscription\Helper\Data;

/**
 * Observer for subscribing customer for SMS or newsletter subscription
 */
class SaveOrderMarketingConsent extends CoreObserver
{
    /**
     * @param Session $customerSession
     * @param CustomerRepository $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param SubscriptionManagerInterface $subscriptionManager
     * @param Data $helper
     * @param ScopeSetting $klaviyoScopeSetting
     * @param Webhook $webhookHelper
     */
    public function __construct(
        private readonly Session $customerSession,
        private readonly CustomerRepository $customerRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly SubscriptionManagerInterface $subscriptionManager,
        private readonly Data $helper,
        private readonly ScopeSetting $klaviyoScopeSetting,
        Webhook $webhookHelper
    ) {
        parent::__construct(
            $webhookHelper,
            $klaviyoScopeSetting
        );
    }

    /**
     * Observer execute method
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if (!$this->klaviyoScopeSetting->isEnabled()) {
            return;
        }
        $quote = $observer->getEvent()->getQuote();
        $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
        $customerId = $this->customerSession->getCustomerId();
        $customer = $customerId ? $this->customerRepository->getById($customerId) : null;

        $phoneNumber = $address->getTelephone() ?? '';
        $sanitizedPhoneNumber = '+'. preg_replace('/[^0-9]/', '', $phoneNumber);
        $validationPhoneNumber = preg_replace('/\D/', '', $phoneNumber);

        if (!$phoneNumber || !(strlen($validationPhoneNumber) === 11 && $validationPhoneNumber[0] === '1')) {
            return;
        }

        if ($quote->getExtensionAttributes()->getSmsSubscription()
            && $this->_klaviyoScopeSetting->getConsentAtCheckoutSMSIsActive()
        ) {
            $this->helper->subscribeSmSToKlaviyoList($quote->getCustomerEmail(), $sanitizedPhoneNumber);
        }
        if ($quote->getExtensionAttributes()->getGeneralSubscription()
            && $this->_klaviyoScopeSetting->getConsentAtCheckoutEmailIsActive()
        ) {
            if ($customer) {
                $storeId = (int)$this->storeManager->getStore()->getId();
                $this->subscriptionManager->subscribeCustomer((int)$customer->getId(), $storeId);
            } else {
                $this->helper->subscribeEmailToKlaviyoList(
                    $quote->getCustomerEmail(),
                    $quote->getCustomerFirstName(),
                    $quote->getCustomerLastName()
                );
            }
        }
    }
}
