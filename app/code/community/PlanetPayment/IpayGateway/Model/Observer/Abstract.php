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
abstract class PlanetPayment_IpayGateway_Model_Observer_Abstract extends Varien_Object {

    /**
     * Returns true if the CIM payment method is available.
     *
     * @return bool
     */
    public function isEnabled() {
        return $this->getConfigData('active');
    }

    /**
     * Returns system config data.
     *
     * @param string $key
     * @return mixed
     */
    public function getConfigData($key) {
        return Mage::helper('ipay')->getConfigData($key);
    }

    /**
     * Returns the logged in user.
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer() {
        if ($this->hasCustomer()) {
            return $this->getData('customer');
        }
        return Mage::getSingleton('customer/session')->getCustomer();
    }

}
