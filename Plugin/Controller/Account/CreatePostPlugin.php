<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   https://magebit.com/code-license
 */

declare(strict_types=1);

namespace Magebit\KlaviyoSubscription\Plugin\Controller\Account;

use Exception;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Customer\Controller\Account\CreatePost;
use Magebit\KlaviyoSubscription\Helper\Data;

/**
 * Subscribe customer to SMS updates during registration process
 */
class CreatePostPlugin
{
    /**
     * @param ScopeSetting $klaviyoScopeSetting
     * @param Data $helper
     */
    public function __construct(
        private readonly ScopeSetting $klaviyoScopeSetting,
        private readonly Data $helper,
    ) {
    }

    /**
     * @param CreatePost $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(CreatePost $subject, $result): mixed
    {
        $email = $subject->getRequest()->getParam('email');

        $isSubscribedSms = $subject->getRequest()->getParam('sms_subscribed_checkbox');

        if ($isSubscribedSms &&
            $this->klaviyoScopeSetting->getConsentAtCheckoutSMSIsActive()
        ) {
            try {
                if ($this->helper->isCustomerSubscribed($email)) {

                    return $result;
                }

                $phoneNumber = $subject->getRequest()->getParam('telephone');
                if (!$phoneNumber) {
                    return $result;
                }

                $sanitizedPhoneNumber = '+' . preg_replace('/\D/', '', $phoneNumber);

                $this->helper->subscribeSmSToKlaviyoList($email, $sanitizedPhoneNumber);

            } catch (Exception $exception) {

                return $result;
            }
        }

        return $result;
    }
}
