<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Block\Cart;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;

/**
 * Exposes quote merge eligibility for the cart merge modal.
 */
class MergeCarts extends Template
{
    /**
     * @param Context $context
     * @param QuoteRepository $quoteRepository
     * @param GuestCartRepositoryInterface $guestCartRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly QuoteRepository $quoteRepository,
        private readonly GuestCartRepositoryInterface $guestCartRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Whether the requested quote (from query param) is eligible for merging.
     *
     * @return bool
     */
    public function isMergeAllowedForRequest(): bool
    {
        $requestedQuoteId = $this->getRequestedQuoteId();
        if ($requestedQuoteId === null) {
            return false;
        }

        $quote = $this->_loadQuoteByIdentifier($requestedQuoteId);

        return $this->_isMergeAllowed($quote);
    }

    /**
     * Raw quoteId parameter value from request (if present).
     *
     * @return string|null
     */
    public function getRequestedQuoteId(): ?string
    {
        $quoteId = $this->getRequest()->getParam('quoteId');

        return is_string($quoteId) && $quoteId !== '' ? $quoteId : null;
    }

    /**
     * Resolve quote by identifier (masked ID or kx_identifier_<base64(customer_id)> token).
     *
     * @param string $quoteId
     * @return CartInterface|null
     */
    private function _loadQuoteByIdentifier(string $quoteId): ?CartInterface
    {
        if (str_contains($quoteId, 'kx_identifier_')) {
            // phpcs:disable Magento2.Functions.DiscouragedFunction
            $customerId = base64_decode(str_replace('kx_identifier_', '', $quoteId));
            if ($customerId === false || $customerId === '') {
                return null;
            }

            try {
                return $this->quoteRepository->getActiveForCustomer((int) $customerId);
            } catch (NoSuchEntityException) {
                return null;
            }
        }

        try {
            // Guest cart repository resolves masked IDs to quotes
            $quote = $this->guestCartRepository->get($quoteId);
            if ($quote instanceof Quote) {
                return $quote;
            }

            $quoteIdValue = $quote->getId();
            if (!$quoteIdValue) {
                return null;
            }

            $resolvedQuote = $this->quoteRepository->get((int) $quoteIdValue);

            return $resolvedQuote instanceof Quote ? $resolvedQuote : null;
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    /**
     * Prevent merging quotes that are inactive or already used for an order.
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    private function _isMergeAllowed(?CartInterface $quote): bool
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
