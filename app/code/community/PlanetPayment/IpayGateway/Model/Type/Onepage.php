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
class PlanetPayment_IpayGateway_Model_Type_Onepage extends Mage_Checkout_Model_Type_Onepage {

    public function initCheckout() {

        $checkout = $this->getCheckout();
        if (is_array($checkout->getStepData())) {
            foreach ($checkout->getStepData() as $step => $data) {
                if (!($step === 'login'
                        || Mage::getSingleton('customer/session')->isLoggedIn() && $step === 'billing')) {
                    $checkout->setStepData($step, 'allow', false);
                }
            }
        }
        
        if (Mage::helper('ipay')->isEnabled()) {
            $checkout->setStepData('currency_selector', 'allow', true);
        }


        /*
         * want to laod the correct customer information by assiging to address
         * instead of just loading from sales/quote_address
         */
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if ($customer) {
            $this->getQuote()->assignCustomer($customer);
        }
        if ($this->getQuote()->getIsMultiShipping()) {
            $this->getQuote()->setIsMultiShipping(false);
            $this->getQuote()->save();
        }
        return $this;
    }

}
