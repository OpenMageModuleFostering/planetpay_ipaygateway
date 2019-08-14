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
class PlanetPayment_IpayGateway_Block_Profile extends Mage_Core_Block_Abstract {

    /**
     * @var array
     */
    protected $_params = array();

    /**
     * Renders the block.
     *
     * @return string
     */
    protected function _toHtml() {
        if ($this->getPaymentProfile() && $this->getType()) {
            switch ($this->getType()) {
                case 'oneline':
                    return $this->_getOneline();
                case 'html':
                    return $this->_getHtml();
            }
        }
        return '';
    }

    /**
     * Sets rendering params.
     *
     * Supported params:
     * 	show_exp_date
     * 	show_address
     *  container_tag
     *
     * @param array $params
     * @return PlanetPayment_IpayGateway_Block_PaymentProfile
     */
    public function setParams(array $params) {
        $this->_params = $params;
        return $this;
    }

    /**
     * Param getter.
     *
     * @param string $param
     * @return mixed
     */
    public function getParam($param) {
        return isset($this->_params[$param]) ? $this->_params[$param] : false;
    }

    /**
     * @return string
     */
    protected function _getOneline() {
        $profile = $this->getPaymentProfile();
        $str = Mage::helper('ipay')->__(
                'Card Type: %s, xxxx-%s, Exp: %s/%s', $profile->getCardTypeName(), $profile->getCardNumberLast4(), $profile->getExpirationMonth(), $profile->getExpirationYear()
        );
        return $str;
    }

    /**
     * @return string
     */
    protected function _getHtml() {
        $profile = $this->getPaymentProfile();
        $tag = $this->getParam('container_tag') ? $this->getParam('container_tag') : 'address';

        $str = '<' . $tag . '>';
        if ($profile->getCardType()) {
            $str .= Mage::helper('ipay')->__('Card Type: %s<br />', $profile->getCardTypeName());
        }
        $str .= Mage::helper('ipay')->__('Card Number: XXXX-%s<br />', $profile->getCardNumberLast4());

        if ($this->getParam('show_exp_date')) {
            $str .= Mage::helper('ipay')->__('Expiration: %s/%s<br />', $profile->getExpirationMonth(), $profile->getExpirationYear());
        }
        if ($this->getParam('ipay')) {
            $address = Mage::getModel('customer/address');
            $profile->exportAddress($address);
            $str .= $address->format('html');
        }
        $str = $str . '</' . $tag . '>';

        return $str;
    }

}
