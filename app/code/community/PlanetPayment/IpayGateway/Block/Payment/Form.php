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
class PlanetPayment_IpayGateway_Block_Payment_Form extends Mage_Payment_Block_Form_Cc {

    protected function _construct() {
        parent::_construct();
        $this->setTemplate('ipay/payment/form.phtml');
    }

    /**
     * Returns true if it's guest checkout.
     *
     * @return bool
     */
    public function isGuestCheckout() {
        return Mage::helper('ipay')->isGuestCheckout();
    }

    /**
     * Returns the logged in user.
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer() {
        return Mage::helper('ipay')->getCustomer();
    }

    /**
     * Returns an array of payment profiles.
     *
     * @return array
     */
    public function getPaymentProfiles() {
        if (!$this->hasData('payment_profiles')) {
            $profilesArray = array();
            $profiles = Mage::getModel('ipay/profile')->getCollection()
                    ->addCustomerFilter($this->getCustomer());
            foreach ($profiles as $profile) {
                if ($profile->getIsVisible()) {  // don't display on frontend
                    $profilesArray[] = $profile;
                }
            }
            $this->setPaymentProfiles($profilesArray);
        }
        return $this->getData('payment_profiles');
    }

    /**
     * @return bool
     */
    public function hasPaymentProfiles() {
        return count($this->getPaymentProfiles()) > 0;
    }

    /**
     * Renders a select dropdown of payment profiles.
     *
     * @return string
     */
    public function getPaymentProfileSelectHtml() {
        $options = array();
        if (Mage::app()->getStore()->isAdmin()) {
            $options['0'] = $this->__('Select one profile');
        }
        foreach ($this->getPaymentProfiles() as $profile) {
            $options[$profile->getId()] = $profile->format('oneline');
        }
        $options[''] = $this->__('New Card');

        $select = Mage::app()->getLayout()->createBlock('core/html_select')
                ->setName('payment[ipay_profile_id]')
                ->setId($this->getMethodCode() . '_payment_profile_id')
                ->setClass('ipay-payment')
                ->setOptions($options);

        return $select->getHtml();
    }

    /**
     * Returns the default payment profile.
     *
     * @return PlanetPayment_IpayGateway_Model_PaymentProfile
     */
    public function getDefaultPaymentProfile() {
        $profiles = $this->getPaymentProfiles();
        foreach ($profiles as $profile) {
            if ($profile->getIsDefault()) {
                return $profile;
            }
        }
        return end($profiles);  // no default yet?
    }

    /**
     * Returns all availale CC types
     * @return type 
     */
    public function getCcAvailableTypes() {
        $types = $this->_getConfig()->getCcTypes();
        $additionalCcTypes = Mage::getModel('ipay/system_config_payment_cctype')->getAdditionalCcTypes();
        $types = array_merge($types, $additionalCcTypes);
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code => $name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
    }

    /**
     * Checking wether tokenie is enabbled
     * @return type bool
     */
    public function isPaymentProfileEnabled() {
        return (bool) Mage::getStoreConfig("payment/ipay/tokenize");
    }

}
