<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Magewire\Component;

use Magewirephp\Magewire\Component;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Checkout subscriptions checkboxes component
 */
class SubscriptionCheckboxes extends Component
{
    protected $listeners = [
        'billing_address_saved' => 'refresh',
        'billing_address_activated' => 'refresh',
        'billing_as_shipping_address_updated' => 'refresh',
        'shipping_address_saved' => 'refresh',
        'shipping_address_activated' => 'refresh'
    ];

    /**
     * @var bool
     */
    public bool $general_subscription = false;

    /**
     * @var bool
     */
    public bool $sms_subscription = false;

    /**
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly CartRepositoryInterface $quoteRepository
    ) {
    }

    /**
     * @return void
     */
    public function mount(): void
    {
        $quote = $this->checkoutSession->getQuote();
        $extensionAttributes = $quote->getExtensionAttributes();

        if ($extensionAttributes) {
            $this->general_subscription = (bool) $extensionAttributes->getGeneralSubscription();
            $this->sms_subscription = (bool) $extensionAttributes->getSmsSubscription();
        }
    }

    /**
     * @param $propertyValue
     * @param $propertyName
     * @return mixed
     */
    public function updated($propertyValue, $propertyName)
    {
        $quote = $this->checkoutSession->getQuote();
        $extensionAttributes = $quote->getExtensionAttributes();

        if ($propertyName === 'general_subscription') {
            $extensionAttributes->setGeneralSubscription($propertyValue);
        } elseif ($propertyName === 'sms_subscription') {
            $extensionAttributes->setSmsSubscription($propertyValue);
        }

        $quote->setExtensionAttributes($extensionAttributes);
        $this->quoteRepository->save($quote);

        return $propertyValue;
    }

    /**
     * Check to show SMS checkbox
     *
     * @return bool
     */
    public function showSmSSubscription(): bool
    {
        $quote = $this->checkoutSession->getQuote();
        $phoneNumber = " ";
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        if ($billingAddress && $billingAddress->getTelephone()) {
            $phoneNumber = $billingAddress->getTelephone();
        } elseif ($shippingAddress && $shippingAddress->getTelephone()) {
            $phoneNumber = $shippingAddress->getTelephone();
        }
        if ($phoneNumber) {
            // Hide SMS subscription for default fallback phone number
            if (trim($phoneNumber) === '1 (222) 222-2222') {
                return false;
            }

            $sanitizedPhone = preg_replace('/\D/', '', $phoneNumber);

            return strlen($sanitizedPhone) === 11 && $sanitizedPhone[0] === '1';
        }

        return true;
    }
}
