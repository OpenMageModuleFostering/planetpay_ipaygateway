<?php

/**
 * One Pica
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to codemaster@onepica.com so we can send you a copy immediately.
 * 
 * @category    PlanetPayment
 * @package     PlanetPayment_IpayGateway
 * @copyright   Copyright (c) 2012 Planet Payment Inc. (http://www.planetpayment.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Planet Payment
 *
 * @category   PlanetPayment
 * @package    PlanetPayment_IpayGateway
 * @author     One Pica Codemaster <codemaster@onepica.com>
 */
class PlanetPayment_IpayGateway_Model_Observer_Checkout extends PlanetPayment_IpayGateway_Model_Observer_Abstract {

    /**
     * Saves the profile if save this card is checked
     *
     * @see Mage_Sales_Model_Order::place()
     * @param Varien_Event_Observer $observer
     */
    public function placeOrderAfter(Varien_Event_Observer $observer) {
        if (!$this->isEnabled()) {
            return;
        }
        $order = $observer->getOrder();
        /* @var $order Mage_Sales_Model_Order */
        $payment = $order->getPayment();
        if ($payment->getMethodInstance()->getCode() == PlanetPayment_IpayGateway_Model_Ipay::METHOD_CODE) {
            if ($payment->getIsVisible() && !$order->getHasCcSaved()) {
                /* @var $profile PlanetPayment_IpayGateway_Model_PaymentProfile */
                $profile = Mage::getModel('ipay/profile');
                Mage::helper('core')->copyFieldset('ipay_paymentprofile_savecc_payment', 'to_paymentprofile', $payment, $profile);
                $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
                if ($customer->getId()) {
                    $billingAddress = $customer->getDefaultBillingAddress();
                    Mage::helper('core')->copyFieldset('ipay_paymentprofile_savecc_address', 'to_paymentprofile', $billingAddress, $profile);
                    $streetAddress = $profile->getStreetAddress();
                    $address = '';
                    if ($streetAddress) {
                        if (is_array($streetAddress)) {
                            foreach ($streetAddress as $addressLine) {
                                $address .= ' ' . $addressLine;
                            }
                        }
                    }
                    $profile->setAddress($address);

                    $requestModel = Mage::getModel('ipay/xml_request')->setIpayPaymentProfile($profile)
                            ->setCustomer($customer);

                    $request = $requestModel->generateNewWalletProfileRequest()
                            ->send();

                    $response = $request->getResponse()
                            ->setPaymentProfile();

                    if ($response->isSuccess()) {
                        $profile = $response->getIpayPaymentProfile();
                        $profile->setIsVisible(true)
                                ->setCustomerId($customer->getId())
                                ->setCardNumberLast4($profile->getCardNumberLast4())
                                ->save();
                        $order->setHasCcSaved(true);
                    }
                }
            }
        }
        return;
    }

}
