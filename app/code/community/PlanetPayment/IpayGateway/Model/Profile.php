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
class PlanetPayment_IpayGateway_Model_Profile extends Mage_Core_Model_Abstract {

    protected $_backupData = array();

    protected function _construct() {
        parent::_construct();
        $this->_init('ipay/profile');
    }

    /**
     * Loads the payment profile by payment profile id 
     * @param string $paymentProfileId
     * @return PlanetPayment_IpayGateway_Model_Profile
     */
    public function loadByPaymentProfileId($paymentProfileId) {
        return $this->load((int) $paymentProfileId);
    }

    /**
     * Exports payment data to $payment
     *
     * @param Varien_Object $payment
     * @return PlanetPayment_IpayGateway_Model_Profile
     */
    public function exportPayment(Varien_Object $payment) {
        Mage::helper('core')->copyFieldset('ipay_paymentprofile_payment', 'to_payment', $this, $payment);
        //$payment->setCcLast4(substr($this->getCardNumber(), -4));
        return $this;
    }

    /**
     * Imports address data to $address
     *
     * @param Varien_Object $address
     * @return PlanetPayment_IpayGateway_Model_Profile
     */
    public function exportAddress(Varien_Object $address) {
        Mage::helper('core')->copyFieldset('ipay_paymentprofile_address', 'to_address', $this, $address);
        return $this;
    }

    /**
     * Formats the profile for display.
     *
     * @param string $type
     * @param array $params
     */
    public function format($type, array $params = array()) {
        $renderer = Mage::app()->getLayout()->createBlock('ipay/profile');
        /* @var $renderer PlanetPayment_IpayGateway_Block_PaymentProfile */
        $renderer->setPaymentProfile($this)
                ->setType($type)
                ->setParams($params);

        return $renderer->toHtml();
    }

    /**
     * Returns the card type, unabbreviated.
     *
     * @return string
     */
    public function getCardTypeName() {
        $type = $this->getCardType();
        foreach (Mage::getSingleton('payment/config')->getCcTypes() as $code => $name) {
            if ($type == $code) {
                return $name;
            }
        }
        return '';
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    /**
     * Concats the exp month and year.
     * 
     * @return string
     */
    public function getExpirationDate() {
        if (!$this->getData('expiration_date')) {
            $this->setExpirationDate($this->getExpirationYear() . '-' . str_pad($this->getExpirationMonth(), 2, '0', STR_PAD_LEFT));
        }
        return $this->getData('expiration_date');
    }

    /**
     * Proxies to the card number if the last4 isn't set.
     *
     * @return string
     */
    public function getCardNumberLast4() {
        if ($this->getData('card_number_last4') === null) {
            if ($this->getCardNumber()) {
                $this->setCardNumberLast4(substr($this->getCardNumber(), -4));
            }
        }
        return $this->getData('card_number_last4');
    }

    /**
     *
     * @param type $id
     * @param type $field
     * @return type 
     */
    public function load($id, $field = null) {
        $this->_backupData = $this->getData();
        return parent::load($id, $field);
    }

    /**
     * After load
     * @return type 
     */
    protected function _afterLoad() {
        foreach ($this->_backupData as $key => $value) {
            if ($value !== null) {
                $this->setData($key, $value);
            }
        }
        return parent::_afterLoad();
    }

    /**
     * HTML-ifies the payment profile.
     *
     * @param bool $showExpirationDate
     * @param bool $showAddress
     * @return string
     * @deprecated use $this->format()
     */
    public function toHtml($showExpirationDate = true, $showAddress = true) {
        $params = array(
            'show_exp_date' => $showExpirationDate,
            'show_address' => $showAddress
        );
        return $this->format('html', $params);
    }

}