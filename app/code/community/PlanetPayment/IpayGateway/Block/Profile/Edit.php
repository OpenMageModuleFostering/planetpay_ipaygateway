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
class PlanetPayment_IpayGateway_Block_Profile_Edit extends Mage_Directory_Block_Data {

    /**
     * The payment profile being edited.
     *
     * @var PlanetPayment_IpayGateway_Model_PaymentProfile
     */
    protected $_paymentProfile = null;

    /**
     * Returns the payment profile being edited.
     *
     * @return PlanetPayment_IpayGateway_Model_PaymentProfile
     */
    public function getIpayPaymentProfile() {
        return $this->_paymentProfile;
    }

    /**
     * Retrieve customer session object
     *
     * @return Mage_Customer_Model_Session
     */
    public function getSession() {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Retrieve customer model
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer() {
        return $this->getSession()->getCustomer();
    }

    protected function _prepareLayout() {
        parent::_prepareLayout();

        $this->_paymentProfile = Mage::getModel('ipay/profile');
        $profileId = $this->getRequest()->getParam('profile_id');
        if ($profileId) {
            $this->_paymentProfile->load($profileId);
            if ($this->_paymentProfile->getIpayProfileId() != $this->getCustomer()->getIpayProfileId()) {
                $this->_paymentProfile->setData(array());
            }
        } else if ($this->getSession()->getProfileFormData()) {
            $this->_paymentProfile->addData($this->getSession()->getProfileFormData(true));
        }

        $title = $this->_paymentProfile->getId() ? 'Edit Store Credit Card Profile' : 'Add New Store Credit Card Profile';
        $this->setTitle($this->__($title));

        return $this;
    }

    /**
     * Returns a payment form block to access CC methods.
     *
     * @return PlanetPayment_IpayGateway_Block_Payment_Form
     */
    public function getPaymentBlock() {
        if (!$this->hasPaymentBlock()) {
            $method = Mage::getModel('ipay/ipay')->setIpayPaymentProfile($this->getIpayPaymentProfile());
            $block = $this->getLayout()->createBlock('ipay/payment_form')
                    ->setIpayPaymentProfile($this->getIpayPaymentProfile())
                    ->setMethod($method);
            $this->setPaymentBlock($block);
        }
        return $this->getData('payment_block');
    }

    /**
     * @return array
     */
    public function getCcAvailableTypes() {
        return $this->getPaymentBlock()->getCcAvailableTypes();
    }

    /**
     * @return array
     */
    public function getCcYears() {
        return $this->getPaymentBlock()->getCcYears();
    }

    /**
     * @return array
     */
    public function getCcMonths() {
        return $this->getPaymentBlock()->getCcMonths();
    }

    /**
     * @return bool
     */
    public function hasVerification() {
        return $this->getPaymentBlock()->hasVerification();
    }

    /**
     * @return string
     */
    public function getBackUrl() {
        return $this->getUrl('*/*/index');
    }

    /**
     * @return string
     */
    public function getPostUrl() {
        return $this->getUrl('*/*/editPost');
    }

    /**
     * @return string
     */
    public function getSuccessUrl() {
        return $this->getUrl('*/*/index');
    }

    /**
     * @return string
     */
    public function getErrorUrl() {
        return $this->getUrl('*/*/*');
    }

    /**
     * Use region codes instead of region IDs.
     *
     * @return string
     */
    public function getRegionsJs() {
        $regionsJs = $this->getData('regions_js');
        if (!$regionsJs) {
            $countryIds = array();
            foreach ($this->getCountryCollection() as $country) {
                $countryIds[] = $country->getCountryId();
            }
            $collection = Mage::getModel('directory/region')->getResourceCollection()
                    ->addCountryFilter($countryIds)
                    ->load();
            $regions = array();
            foreach ($collection as $region) {
                if (!$region->getRegionId()) {
                    continue;
                }
                $regions[$region->getCountryId()][$region->getCode()] = array(
                    'code' => $region->getCode(),
                    'name' => $region->getName()
                );
            }
            $regionsJs = Mage::helper('core')->jsonEncode($regions);
        }
        return $regionsJs;
    }

}
