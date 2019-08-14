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
require_once 'Mage/Checkout/controllers/OnepageController.php';

class PlanetPayment_IpayGateway_OnepageController extends Mage_Checkout_OnepageController {

    /**
     * Save payment ajax action
     *
     * Sets either redirect or a JSON response
     */
    public function savePaymentAction() {

        if ($this->_expireAjax()) {
            return;
        }
        try {
            if (!$this->getRequest()->isPost()) {
                $this->_ajaxRedirectResponse();
                return;
            }

            // set payment to quote
            $result = array();
            $data = $this->getRequest()->getPost('payment', array());
            $result = $this->getOnepage()->savePayment($data);

            // get section and redirect data
            $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
            if (empty($result['error']) && !$redirectUrl) {
                if (isset($data['method']) && $data['method'] == PlanetPayment_IpayGateway_Model_Ipay::METHOD_CODE) {
                    try {
                        //Getting the Quote Object
                        $quote = $this->getOnepage()->getQuote();
                        $payment = $quote->getPayment();
                        $method = $payment->getMethodInstance();

                        $paymentType = $method->getPaymentType();
                        
                        if ($paymentType == PlanetPayment_IpayGateway_Model_Ipay::PAYMENT_SERVICE_PYC) {
                            // //Preparing & Sending request for PYC Rate Query
                            $quote->setIpayProfileId($payment->getIpayProfileId());
                            $request = $this->_getIpayRequestModel()
                                    ->setPayment($payment)
                                    ->setQuote($quote)
                                    ->setAmount($quote->getGrandTotal());

                            $request->generatePycCurrencyRateQueryRequest()
                                    ->send();

                            //Getting Response Object
                            $response = $request->getResponse();
                            //Checking whether the request succeed
                            if ($response->isSuccess()) {
                                $quote->setIpayExchangeRate($response->getPycExchangeRate())
								->setIpayMarkup($response->getMarkUp())
								->setIpayServiceUsed(Mage::getStoreConfig('payment/ipay/service'))
                                ->save();
                                //Loading Currency section if request succeed
                                $this->loadLayout('checkout_onepage_ipay');
                                $result['goto_section'] = 'currency_selector';
                                $result['update_section'] = array(
                                    'name' => 'currency_selector',
                                    'html' => $this->_getCurrencySelectorHtml($response)
                                );
                            } else {
                                //Throwing an error to load the review section
                                Mage::throwException("Ipay Request Failed");
                            }
                        } else {
                            //Throwing an error to load the review section
                            Mage::throwException("Payment service is set to MCP.");
                        }
                    } catch (Exception $e) {
                        $quote->setIpayExchangeRate(null)
                                        ->save();
                        $this->loadLayout('checkout_onepage_review');
                        $result['goto_section'] = 'review';
                        $result['update_section'] = array(
                            'name' => 'review',
                            'html' => $this->_getReviewHtml()
                        );
                    }
                } else {
                    $this->loadLayout('checkout_onepage_review');
                    $result['goto_section'] = 'review';
                    $result['update_section'] = array(
                        'name' => 'review',
                        'html' => $this->_getReviewHtml()
                    );
                }
            }
            if ($redirectUrl) {
                $result['redirect'] = $redirectUrl;
            }
        } catch (Mage_Payment_Exception $e) {
            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = $this->__('Unable to set Payment Method.');
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * Save the selected currency to the Payment for PYC
     */
    public function saveCurrencyAction() {
        $this->_expireAjax();
        if ($this->getRequest()->isPost()) {
            // get section and redirect data
            $data = $this->getRequest()->getPost('payment',array());
            if(!isset($data['selected_currency'])) {
                $result['error'] = true;
                $result['message'] = 'Please select a currency';
                $this->getOnepage()->getQuote()->setIpayCurrencyCode(null)->save();
            } else {
                $quote = $this->getOnepage()->getQuote();
                $quote->getPayment()->setIpayCurrencyCode($data['selected_currency']);
                $quote->save();
            }
            $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
            if (empty($result['error']) && !$redirectUrl) {
                $this->loadLayout('checkout_onepage_review');
                $result['goto_section'] = 'review';
                $result['update_section'] = array(
                    'name' => 'review',
                    'html' => $this->_getReviewHtml()
                );
            }
            if ($redirectUrl) {
                $result['redirect'] = $redirectUrl;
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    protected function _getIpayRequestModel() {
        return Mage::getModel('ipay/xml_request');
    }

    /**
     * Get order review step html
     *
     * @return string
     */
    protected function _getCurrencySelectorHtml($response) {
        return $this->getLayout()->getBlock('root')->setResponse($response)->toHtml();
    }

}
