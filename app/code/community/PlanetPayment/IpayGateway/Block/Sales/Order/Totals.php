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
class PlanetPayment_IpayGateway_Block_Sales_Order_Totals extends Mage_Sales_Block_Order_Totals {

    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _initTotals() {
        $source = $this->getSource();

        $this->_totals = array();
        $this->_totals['subtotal'] = new Varien_Object(array(
                    'code' => 'subtotal',
                    'value' => $source->getSubtotal(),
                    'label' => $this->__('Subtotal')
                ));


        /**
         * Add shipping
         */
        if (!$source->getIsVirtual() && ((float) $source->getShippingAmount() || $source->getShippingDescription())) {
            $this->_totals['shipping'] = new Varien_Object(array(
                        'code' => 'shipping',
                        'field' => 'shipping_amount',
                        'value' => $this->getSource()->getShippingAmount(),
                        'label' => $this->__('Shipping & Handling')
                    ));
        }

        /**
         * Add discount
         */
        if (((float) $this->getSource()->getDiscountAmount()) != 0) {
            if ($this->getSource()->getDiscountDescription()) {
                $discountLabel = $this->__('Discount (%s)', $source->getDiscountDescription());
            } else {
                $discountLabel = $this->__('Discount');
            }
            $this->_totals['discount'] = new Varien_Object(array(
                        'code' => 'discount',
                        'field' => 'discount_amount',
                        'value' => $source->getDiscountAmount(),
                        'label' => $discountLabel
                    ));
        }

        $this->_totals['grand_total'] = new Varien_Object(array(
                    'code' => 'grand_total',
                    'field' => 'grand_total',
                    'strong' => true,
                    'value' => $source->getGrandTotal(),
                    'label' => $this->__('Grand Total')
                ));

        /**
         * Base grandtotal
         */
        if ($this->needDisplayBaseGrandtotal()) {
            $quoteId = $this->getOrder()->getQuoteId();
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if($quote->getId()) {
                $exchangeRate = $quote->getIpayExchangeRate();
                if ($this->_isPyc() && $exchangeRate) {
                    $total = $source->getBaseGrandTotal();
                    $currency = Mage::app()->getLocale()->currency($this->getOrder()->getPayment()->getIpayCurrencyCode())->getSymbol() . " " . number_format($total * $exchangeRate, 2);
                } elseif ($this->_isMcp()) {
                    $total = $source->getGrandTotal();
                    $currency = Mage::app()->getStore()->getCurrentCurrency()->format($total, array(), true);
                } else {
                    $total = $source->getBaseGrandTotal();
                    $currency = Mage::app()->getStore()->getBaseCurrency()->format($total, array(), true);
                }
                $this->_totals['base_grandtotal'] = new Varien_Object(array(
                            'code' => 'base_grandtotal',
                            'value' => $currency,
                            'label' => $this->__('Grand Total to be Charged'),
                            'is_formated' => true,
                        ));
            }
        }
        return $this;
    }

    /**
     * Check if we have display grand total in base currency
     *
     * @return bool
     */
    public function needDisplayBaseGrandtotal() {
        $order = $this->getOrder();
        if ($order->getBaseCurrencyCode() != $order->getQuoteCurrencyCode() ||
                ($this->_isPyc() && $order->getQuoteCurrencyCode() != $order->getPayment()->getIpayCurrencyCode())) {
            return true;
        }
        return false;
    }

    protected function _isPyc() {
        return Mage::getModel('ipay/ipay')->getConfigData("service") == PlanetPayment_IpayGateway_Model_Ipay::PAYMENT_SERVICE_PYC;
    }

    protected function _isMcp() {
        return Mage::getModel('ipay/ipay')->getConfigData("service") == PlanetPayment_IpayGateway_Model_Ipay::PAYMENT_SERVICE_MCP;
    }

}
