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
class PlanetPayment_IpayGateway_Model_Currency_Import_Ipay extends Mage_Directory_Model_Currency_Import_Abstract {

    protected $_messages = array();
    protected $_rates = array();

    public function __construct() {
        $request = $this->_getRequestModel()
                ->generateCurrencyRateLookUpRequest()
                ->send();
        if ($request->getResponse()->isSuccess()) {
            $this->_rates = $this->_formatConvertionXml($request->getResponse());
        } else {
            Mage::throwException("Communication Error! Please try later");
        }
    }

    protected function _convert($currencyFrom, $currencyTo, $retry=0) {
        if (count($this->_rates)) {
            try {
                $nativeCurrency = $this->_getRequestModel()->getNativeCurrency();
                if ($currencyFrom != $nativeCurrency) {
                    $this->_messages[] = Mage::helper('ipay')->__('Ipay Native currency is different from store base currency');
                } else if (isset($this->_rates[$currencyTo])){
                    return $this->_rates[$currencyTo];
                } else {
                    $this->_messages[] = Mage::helper('ipay')->__("Unable to retrieve the conversion rate from %s to %s",$currencyFrom, $currencyTo);
                }
            } catch (Exception $e) {
                if ($retry == 0) {
                    $this->_convert($currencyFrom, $currencyTo, 1);
                } else {
                    $this->_messages[] = Mage::helper('ipay')->__('Cannot retrieve rate from Ipay');
                }
            }
        } else {
            $this->_messages[] = Mage::helper('ipay')->__("Unable to retrieve the conversion rate from %s to %s",$currencyFrom, $currencyTo);
        }
    }

    protected function _formatConvertionXml($response) {
        $responsexml = $response->getXmlContent();
        $currencyRates = array();
        if ($responsexml) {
            $rates = $responsexml->FIELDS->RATES;
            foreach ($rates->RATE as $rate) {
                $attributes = $rate->CURRENCY_CODE->Attributes();
                $currencyRates[(string) $attributes['ALPHA']] = 1 / (float) $rate->EXCHANGE_RATE;
            }
        }
        return $currencyRates;
    }

    protected function _getRequestModel() {
        return Mage::getSingleton('ipay/xml_request');
    }

}
