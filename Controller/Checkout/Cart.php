<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Controller\Checkout;

use Klaviyo\Reclaim\Controller\Checkout\Cart as KlaviyoCart;
use Magento\Checkout\Model\Cart as CartModel;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Model\Quote;
use Magento\Customer\Model\Session;

class Cart extends KlaviyoCart
{
    /**
     * @param Session $customerSession
     * @param CartModel $cart
     * @param Context $context
     * @param QuoteRepository $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        private readonly Session $customerSession,
        CartModel $cart,
        Context $context,
        QuoteRepository $quoteRepository,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        parent::__construct(
            $cart,
            $context,
            $quoteRepository,
            $quoteIdMaskFactory
        );
    }

    /**
     * Controller execute method
     *
     * @return Redirect
     */
    public function execute()
    {
        $params = $this->request->getParams();
        $quoteId = isset($params['quote_id']) ? $params['quote_id'] : "";

        if ($this->cart->getItemsCount()) {
            $redirect = $this->resultRedirectFactory->create();
            $redirect->setPath(
                'checkout/cart',
                [
                    '_query' => [
                        'mergeCarts' => 1,
                        'quoteId' => $quoteId,
                    ]
                ]
            );

            return $redirect;
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/cart', ['_query' => $params]);
        unset($params['quote_id']);
        $customer = $this->customerSession->getCustomer();

        // Check if the quote_id has kx_identifier, if yes, retrieve active quote for customer,
        //if not get QuoteId from masked QuoteId
        if (strpos($quoteId, "kx_identifier_") !== false) {
// phpcs:disable Magento2.Functions.DiscouragedFunction.Discouraged
            $customerId = base64_decode(str_replace("kx_identifier_", "", $quoteId));
            try {
                $sourceQuote = $this->quoteRepository->getActiveForCustomer($customerId);
                if (!$this->_isMergeAllowed($sourceQuote)) {
                    return $redirect;
                }
                $quote = $this->cart->getQuote();

                $customer = $this->customerSession->getCustomer();
                $quote->setCustomerId($customer->getId());
                $quote->setCustomerEmail($customer->getEmail());

                $this->quoteRepository->save(
                    $quote->merge($sourceQuote)->collectTotals()
                );
            } catch (NoSuchEntityException $ex) {
                return $redirect;
            }
        } else {
            try {
                /** @phpstan-ignore-next-line */
                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($quoteId, 'masked_id');
                $sourceQuote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
                if (!$this->_isMergeAllowed($sourceQuote)) {
                    return $redirect;
                }
                $quote = $this->cart->getQuote();

                $customer = $this->customerSession->getCustomer();
                $quote->setCustomerId($customer->getId());
                $quote->setCustomerEmail($customer->getEmail());

                $this->quoteRepository->save(
                    $quote->merge($sourceQuote)->collectTotals()
                );
            } catch (NoSuchEntityException $ex) {
                return $redirect;
            }
        }

        return $redirect;
    }

    /**
     * Prevent merging quotes that are inactive or already used for an order.
     *
     * @param Quote|null $quote
     * @return bool
     */
    private function _isMergeAllowed(?Quote $quote): bool
    {
        if ($quote === null) {
            return false;
        }

        if (!$quote->getIsActive()) {
            return false;
        }

        if ($quote->getReservedOrderId()) {
            return false;
        }

        return true;
    }
}
