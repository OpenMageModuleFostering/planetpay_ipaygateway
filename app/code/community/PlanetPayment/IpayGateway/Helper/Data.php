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
class PlanetPayment_IpayGateway_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * Debug log setting from the config.
     *
     * @var bool
     */
    protected $_debugLog = null;

    /**
     * Logs a debug message to db
     *
     * @param mixed $message
     * @param string $logFile
     */
    public function log($request, $response, $currency= false) {
        $global = Mage::getStoreConfig('planet_payment/ipay_logging/mode');
        $method = $this->getConfigData('logging');
        $currencyConvertor = Mage::getStoreConfig('currency/ipay/logging');
        $logModel = Mage::getModel('ipay/log');
        if ($global) {
            if (!$currency && $method) {
                $logModel->setRequest($request)
                        ->setResponse($response)
                        ->save();
            } else if($currency && $currencyConvertor) {
                $logModel->setRequest($request)
                        ->setResponse($response)
                        ->save();
            }
        }
        
        return $logModel;
    }

    /**
     * Returns data from the store config.
     *
     * @param string $key
     * @return string
     */
    public function getConfigData($key) {
        $path = 'payment/ipay/' . $key;
        return Mage::getStoreConfig($path);
    }

    /**
     * Returns the checkout session.
     *
     * @return Mage_Core_Model_Session_Abstract
     */
    public function getCheckout() {
        if (Mage::app()->getStore()->isAdmin()) {
            return Mage::getSingleton('adminhtml/session_quote');
        } else {
            return Mage::getSingleton('checkout/session');
        }
    }

    /**
     * Returns the quote.
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote() {
        return $this->getCheckout()->getQuote();
    }

    /**
     * Returns the logged in user.
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer() {
        return $this->getQuote()->getCustomer();
    }

    /**
     * Returns true if it's guest checkout.
     *
     * @return bool
     */
    public function isGuestCheckout() {
        if (Mage::app()->getStore()->isAdmin()) {
            return!$this->getCheckout()->getCustomerId();
        } else {
            return $this->getQuote()->getCheckoutMethod() == Mage_Checkout_Model_Type_Onepage::METHOD_GUEST;
        }
    }

    /**
     * Is the payment method enabled?
     *
     * @return bool
     */
    public function isEnabled() {
        return (bool) $this->getConfigData('active');
    }

}