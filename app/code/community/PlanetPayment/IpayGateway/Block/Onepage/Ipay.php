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
class PlanetPayment_IpayGateway_Block_Onepage_Ipay extends Mage_Checkout_Block_Onepage_Abstract {

    protected function _construct() {
        $this->getCheckout()->setStepData('currency_selector', array(
            'label' => Mage::helper('ipay')->__('Payment Currency (Planet Payment)'),
            'is_show' => true
        ));

        parent::_construct();
    }

    public function getCurrencyOptions() {
        return $this->getResponse()->getPycCurrencyOptions();
    }
    
    public function getExchangeRate() {
        return $this->getResponse()->getExchangeRate();
    }

    public function getMarkUp() {
        return $this->getResponse()->getMarkUp();
    }
    
    public function hasOptions() {
        if ($this->getResponse()) {
            return true;
        }

        return false;
    }

}