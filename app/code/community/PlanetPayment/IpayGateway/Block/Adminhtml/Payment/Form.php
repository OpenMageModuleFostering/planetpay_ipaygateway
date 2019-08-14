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
class PlanetPayment_IpayGateway_Block_Adminhtml_Payment_Form extends PlanetPayment_IpayGateway_Block_Payment_Form {

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
            foreach ($profiles as $profile) {  // include all profiles regardless of visibility
                $profilesArray[] = $profile;
            }

            $this->setPaymentProfiles($profilesArray);
        }
        return $this->getData('payment_profiles');
    }

    /**
     * Returns an empty profile or the one chosen with the last request.
     *
     * @return PlanetPayment_IpayGateway_Model_Profile
     */
    public function getDefaultPaymentProfile() {
        if ($this->getMethod()->getInfoInstance()->getIpayProfileId()) {
            return $this->getSelectedProfile();
        }
        return Mage::getModel('ipay/profile');
    }

    /**
     * Returns the profile set in the method instance.
     *
     * @return PlanetPayment_IpayGateway_Model_Profile
     */
    public function getSelectedProfile() {
        $profile = Mage::getModel('ipay/profile');
        $profileId = $this->getMethod()->getInfoInstance()->getIpayProfileId();
        if ($profileId) {
            $profile->loadByPaymentProfileId($profileId);
        }
        return $profile;
    }

}
