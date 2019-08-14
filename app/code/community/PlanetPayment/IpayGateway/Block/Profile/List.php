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
class PlanetPayment_IpayGateway_Block_Profile_List extends Mage_Core_Block_Template {

    /**
     * Retrieve customer model
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer() {
        return Mage::getSingleton('customer/session')->getCustomer();
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
        }
        $this->setPaymentProfiles($profilesArray);
        return $this->getData('payment_profiles');
    }

    /**
     * @return string
     */
    public function getDeleteUrl() {
        return $this->getUrl('*/*/delete');
    }

    /**
     * @return string
     */
    public function getAddUrl() {
        return $this->getUrl('*/*/new');
    }

    /**
     * @param PlanetPayment_IpayGateway_Model_PaymentProfile $profile
     * @return string
     */
    public function getEditUrl(PlanetPayment_IpayGateway_Model_PaymentProfile $profile) {
        return $this->getUrl('*/*/edit', array('profile_id' => $profile->getId()));
    }

}
