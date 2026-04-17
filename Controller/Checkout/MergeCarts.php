<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Controller\Checkout;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Checkout\Model\Cart;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;

/**
 * Merge carts from Klaviyo controller
 */
class MergeCarts implements HttpGetActionInterface
{
    /**
     * @param Session $customerSession
     * @param RedirectFactory $resultRedirectFactory
     * @param Cart $cart
     * @param QuoteRepository $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param GuestCartRepositoryInterface $guestCartRepository
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly Session $customerSession,
        private readonly RedirectFactory $resultRedirectFactory,
        private readonly Cart $cart,
        private readonly QuoteRepository $quoteRepository,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly GuestCartRepositoryInterface $guestCartRepository,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * Controller execute method
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/cart');
        if ($this->request->getParam('replace')) {
            $this->_replaceCart();

            return $redirect;
        }

        $this->_mergeCart();

        return $redirect;
    }

    /**
     * Replace current cart with cart from Klaviyo
     *
     * @return void
     */
    /**
     * Replace current cart with cart from Klaviyo.
     *
     * @return void
     */
    private function _replaceCart(): void
    {
        /** @var Quote $sourceQuote */
        $sourceQuote = $this->_getQuote();

        if (!$sourceQuote) {
            return;
        }

        $quote = $this->cart->getQuote();
        $quote->removeAllItems();

        $this->quoteRepository->save(
            $quote->merge($sourceQuote)->collectTotals()
        );
    }

    /**
     * Merge cart from Klaviyo with current cart
     *
     * @return void
     */
    /**
     * Merge cart from Klaviyo with current cart.
     *
     * @return void
     */
    private function _mergeCart(): void
    {
        /** @var Quote $sourceQuote */
        $sourceQuote = $this->_getQuote();

        if (!$sourceQuote) {
            return;
        }

        $quote = $this->cart->getQuote();

        $customer = $this->customerSession->getCustomer();
        $quote->setCustomerId($customer->getId());
        $quote->setCustomerEmail($customer->getEmail());

        $this->quoteRepository->save(
            $quote->merge($sourceQuote)->collectTotals()
        );
    }

    /**
     * Get cart from Klaviyo params
     *
     * @return CartInterface|null
     */
    /**
     * Get cart from Klaviyo params.
     *
     * @return CartInterface|null
     */
    private function _getQuote(): ?CartInterface
    {
        $params = $this->request->getParams();
        $quoteId = isset($params['quoteId']) ? $params['quoteId'] : "";

        unset($params['quoteId']);

        if (strpos($quoteId, "kx_identifier_") !== false) {
            // phpcs:disable Magento2.Functions.DiscouragedFunction.Discouraged
            $customerId = base64_decode(str_replace("kx_identifier_", "", $quoteId));
            try {
                $quote = $this->quoteRepository->getActiveForCustomer($customerId);
            } catch (NoSuchEntityException $ex) {
                $quote = null;
            }

            return $this->_isMergeAllowed($quote) ? $quote : null;
        }

        try {
            $quote = $this->guestCartRepository->get($quoteId);
        } catch (NoSuchEntityException $ex) {
            $quote = null;
        }

        return $this->_isMergeAllowed($quote) ? $quote : null;
    }

    /**
     * Determine whether the quote can be merged into the current cart.
     *
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
