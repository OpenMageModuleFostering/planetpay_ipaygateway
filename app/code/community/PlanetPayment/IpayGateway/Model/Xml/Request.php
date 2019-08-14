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
class PlanetPayment_IpayGateway_Model_Xml_Request extends PlanetPayment_IpayGateway_Model_Xml_Abstract {

    /**
     * Returns CC Expiration date in Palnet Payment's Format
     */
    protected function _getCcExpiration($month, $year) {
        if (strlen($month) == 1) {
            $month = '0' . $month;
        }

        return (string) $month . substr($year, -2);
    }

    /**
     * Returns the response model object
     * @return object PlanetPayment_IpayGateway_Model_Xml_Response 
     */
    protected function _getResponseModel() {
        return Mage::getModel('ipay/xml_response');
    }

    /**
     * Returns the root node. Two options here, either get a simple xml root node
     * as a varien_simplexml object or get the complete encrypted request wrapped in
     * root node.
     * @param bool $afterEncrypt
     * @param string $encryptedXml
     * @return Varien_Simplexml_Element 
     */
    protected function _getRootNode($afterEncrypt = false, $encryptedXml = false) {
        $hasEncryption = $this->_hasEncryption();

        $key = $this->_getConfig('key', 'general');
        $encryption = $hasEncryption ? '1' : '0';

        if ($afterEncrypt) {
            $rootNodeString = '<REQUEST KEY="' . $key . '" PROTOCOL="1" ENCODING="' . $encryption . '" FMT="1">' . $encryptedXml . '</REQUEST>';
        } else {
            $rootNodeString = '<REQUEST KEY="' . $key . '" PROTOCOL="1" ENCODING="' . $encryption . '" FMT="1"/>';
        }
        return new Varien_Simplexml_Element($rootNodeString);
    }
	
	/**
	 * Condition the address text passed in to be limited to 30 characters
	 * 
	 * @param type $address 
	 */
	protected function _conditionAddress($text) {
		if(!$text || $text=="" || strlen($text) <= 30) {
			return $text;
		}
		
		/** 
		 * Two instances of calls in this file used to call htmlentities on the
		 * value, which I don't think we actually want. HTML Encoding will only
		 * make the values longer...
		 */
		//htmlentities($profile->getAddress(), ENT_QUOTES);
		return substr($text, 0, 30);
	}
	
	/**
	 * If postal code is longer than 9 characters, strip to the first five. This
	 * handles U.S. formatted postal codes inserted with a hyphen (i.e. 12345-6789)
	 *
	 * In that case, 12345 will be returned. Any text shorter than 9 characters
	 * passed to this function will be returned unchanged.
	 * @param type $text
	 * @return type 
	 */
	protected function _conditionPostalCode($text) {
		if(!$text || $text=="" || strlen($text) <= 9) {
			return $text;
		}
		
		return substr($text, 0, 5);
	}

    /**
     * Generates the Transaction Xml for authorization
     * @return PlanetPayment_IpayGateway_Model_Xml_Request 
     */
    public function generateRequestForPycAuth() {
        try {
            $payment = $this->getPayment();

            $profile = $this->_getProfile($payment->getIpayProfileId());
            $quote = Mage::helper('ipay')->getQuote();
            
            $billingAddress = $quote->getBillingAddress();
            
            $hasEncryption = $this->_hasEncryption();

            $key = $this->_getConfig('key', 'general');
            $encryption = $hasEncryption ? '1' : '0';

            $request = $this->_getRootNode();
            $transaction = $request->addChild('TRANSACTION');
            $fields = $transaction->addChild('FIELDS');
            $fields->addChild('PIN', $this->_getConfig('pin', 'general'));
            $fields->addChild('SERVICE', 'CC');
            $fields->addChild('SERVICE_TYPE', 'DEBIT');
            $fields->addChild('SERVICE_SUBTYPE', 'AUTH');
            $fields->addChild('SERVICE_FORMAT', '1010');
            $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'general'));
            //If a profile selected by customer
            if ($profile->getAccountId()) {
                $fields->addChild('ACCOUNT_ID', $profile->getAccountId());
            } else {
                $fields->addChild('ACCOUNT_NUMBER', $payment->getCcNumber());
                $fields->addChild('EXPIRATION', $this->_getCcExpiration($payment->getCcExpMonth(), $payment->getCcExpYear()));
                $fields->addChild('CVV', $payment->getCcCid());
                $fields->addChild('FIRST_NAME', $billingAddress->getFirstname());
                $fields->addChild('LAST_NAME', $billingAddress->getLastname());
                $fields->addChild('ADDRESS', $this->_conditionAddress($billingAddress->getStreet(1)));
				$fields->addChild('STATE', $billingAddress->getRegionCode());
                $fields->addChild('CITY', $billingAddress->getCity());
                $fields->addChild('POSTAL_CODE', $this->_conditionPostalCode($billingAddress->getPostcode()));
            }

            $fields->addChild('AMOUNT', $this->getAmount());
            $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($this->getNativeCurrency()));
            //If a different currecy is selected by the customer
            if ($payment->getId() && $payment->getIpayCurrencyCode()) {
                if ($payment->getIpayCurrencyCode() == $this->getNativeCurrency()) {
                    $fields->addChild('CURRENCY_INDICATOR', '0');
                } else {
                    $fields->addChild('CURRENCY_INDICATOR', '2');
                }
            } else {
                $fields->addChild('CURRENCY_INDICATOR', '0');
            }
            $fields->addChild('TRANSACTION_INDICATOR', '7');
			$fields->addChild('FESP_IND', '9');
			$fields->addChild('USER_DATA_0', $this->_getClientName());

            $this->setTransactionForLog($request);


            if ($hasEncryption) {
                $this->_encryptRequest($request);
            }
            $this->setTransaction($request);
        } catch (Exception $e) {
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generates the Transaction Xml for authorization
     * @return PlanetPayment_IpayGateway_Model_Xml_Request 
     */
    public function generateRequestForMcpAuth() {
        try {
            $payment = $this->getPayment();

            $profile = $this->_getProfile($payment->getIpayProfileId());
            $quote = Mage::helper('ipay')->getQuote();
            $quoteCurrency = $quote->getQuoteCurrencyCode();
            $billingAddress = $quote->getBillingAddress();
            $hasEncryption = $this->_hasEncryption();

            $key = $this->_getConfig('key', 'general');
            $encryption = $hasEncryption ? '1' : '0';

            $request = $this->_getRootNode();
            $transaction = $request->addChild('TRANSACTION');
            $fields = $transaction->addChild('FIELDS');
            $fields->addChild('PIN', $this->_getConfig('pin', 'general'));
            $fields->addChild('SERVICE', 'CC');
            $fields->addChild('SERVICE_TYPE', 'DEBIT');
            $fields->addChild('SERVICE_SUBTYPE', 'AUTH');
            $fields->addChild('SERVICE_FORMAT', '1010');
            $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'general'));
            //If a profile selected by customer
            if ($profile->getAccountId()) {
                $fields->addChild('ACCOUNT_ID', $profile->getAccountId());
            } else {
                $fields->addChild('ACCOUNT_NUMBER', $payment->getCcNumber());
                $fields->addChild('EXPIRATION', $this->_getCcExpiration($payment->getCcExpMonth(), $payment->getCcExpYear()));
                $fields->addChild('CVV', $payment->getCcCid());
                $fields->addChild('FIRST_NAME', $billingAddress->getFirstname());
                $fields->addChild('LAST_NAME', $billingAddress->getLastname());
                $fields->addChild('ADDRESS', $this->_conditionAddress($billingAddress->getStreet(1)));
				$fields->addChild('STATE', $billingAddress->getRegionCode());
                $fields->addChild('CITY', $billingAddress->getCity());
                $fields->addChild('POSTAL_CODE', $this->_conditionPostalCode($billingAddress->getPostcode()));
            }

            $fields->addChild('AMOUNT', $this->getAmountInStoreCurrency());
            $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($quoteCurrency));
            if ($quoteCurrency != $this->getNativeCurrency()) {
                $fields->addChild('CURRENCY_INDICATOR', '1');
            } else {
                $fields->addChild('CURRENCY_INDICATOR', '0');
            }
            $fields->addChild('TRANSACTION_INDICATOR', '7');
			$fields->addChild('FESP_IND', '9');
			$fields->addChild('USER_DATA_0', $this->_getClientName());

            $this->setTransactionForLog($request);


            if ($hasEncryption) {
                $this->_encryptRequest($request);
            }
            $this->setTransaction($request);
        } catch (Exception $e) {
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generates the Transaction Xml Fo Pyc Currency rate query
     * @return PlanetPayment_IpayGateway_Model_Xml_Request 
     */
    public function generatePycCurrencyRateQueryRequest() {
        try {
            $payment = $this->getPayment();
            $profile = $this->_getProfile($payment->getIpayProfileId());
            $quote = $this->getQuote();
            $currencyCode = $this->_getCurrencyIsoCode($quote->getQuoteCurrencyCode());
            $hasEncryption = $this->_hasEncryption();

            $key = $this->_getConfig('key', 'general');
            $encryption = $hasEncryption ? '1' : '0';

            $request = $this->_getRootNode();
            $transaction = $request->addChild('TRANSACTION');
            $fields = $transaction->addChild('FIELDS');
            $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'general'));
            $fields->addChild('PIN', $this->_getConfig('pin', 'general'));
            $fields->addChild('SERVICE_FORMAT', '0000');
            $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($this->getNativeCurrency()));
            $fields->addChild('CURRENCY_INDICATOR', '2');
            $fields->addChild('SERVICE', 'CURRENCY');
            $fields->addChild('SERVICE_TYPE', 'RATE');
            $fields->addChild('SERVICE_SUBTYPE', 'QUERY');
            $fields->addChild('AMOUNT', $this->getAmount());
            if ($profile->getAccountId()) {
                $fields->addChild('ACCOUNT_ID', $profile->getAccountId());
            } else {
                $fields->addChild('EXPIRATION', $this->_getCcExpiration($payment->getCcExpMonth(), $payment->getCcExpYear()));
                $fields->addChild('ACCOUNT_NUMBER', $payment->getCcNumber());
            }
            $fields->addChild('QUERY_TYPE', '0');
			$fields->addChild('FESP_IND', '9');

            $this->setTransactionForLog($request);


            if ($hasEncryption) {
                $this->_encryptRequest($request);
            }

            $this->setTransaction($request);
        } catch (Exception $e) {
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Transaction request for currency rate look up for mcp
     * @return PlanetPayment_IpayGateway_Model_Xml_Request 
     */
    public function generateCurrencyRateLookUpRequest() {
        try {
            $hasEncryption = $this->_hasEncryption();

            $key = $this->_getConfig('key', 'general');
            $encryption = $hasEncryption ? '1' : '0';
            $request = $this->_getRootNode();
            $transaction = $request->addChild('TRANSACTION');
            $fields = $transaction->addChild('FIELDS');
            $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'general'));
            $fields->addChild('PIN', $this->_getConfig('pin', 'general'));
            $fields->addChild('SERVICE_FORMAT', '0000');
            $fields->addChild('CURRENCY_INDICATOR', '1');
            $fields->addChild('SERVICE', 'CURRENCY');
            $fields->addChild('SERVICE_TYPE', 'RATE');
            $fields->addChild('SERVICE_SUBTYPE', 'QUERY');
            $fields->addChild('QUERY_TYPE', '1');
			$fields->addChild('FESP_IND', '9');

            $this->setTransactionForLog($request);
            $this->setCurrencyRate(true);

            if ($hasEncryption) {
                $this->_encryptRequest($request);
            }

            $this->setTransaction($request);
        } catch (Exception $e) {
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generating transaction request for adding a new Client Profille
     * @return type 
     */
    public function generateNewWalletProfileRequest() {
        try {
            $profile = $this->getIpayPaymentProfile();
            if ($profile) {
                $hasEncryption = $this->_hasEncryption();

                $key = $this->_getConfig('key', 'general');
                $encryption = $hasEncryption ? '1' : '0';
                $request = $this->_getRootNode();
                $transaction = $request->addChild('TRANSACTION');
                $fields = $transaction->addChild('FIELDS');
                $fields->addChild('SERVICE', 'WALLET');
                $fields->addChild('SERVICE_TYPE', 'CLIENT');
                $fields->addChild('SERVICE_SUBTYPE', 'INSERT');
                $fields->addChild('SERVICE_FORMAT', '1010');
                $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'general'));
                $fields->addChild('PIN', $this->_getConfig('pin', 'general'));
                $fields->addChild('FIRST_NAME', htmlentities($profile->getFirstName(), ENT_QUOTES));
                $fields->addChild('LAST_NAME', htmlentities($profile->getLastName(), ENT_QUOTES));
                $fields->addChild('ADDRESS', $this->_conditionAddress($profile->getAddress()));
                $fields->addChild('CITY', htmlentities($profile->getCity(), ENT_QUOTES));
                $fields->addChild('POSTAL_CODE', $this->_conditionPostalCode($profile->getZip()));
                $fields->addChild('STATE', htmlentities($profile->getState(), ENT_QUOTES));
                $fields->addChild('COUNTRY', htmlentities($profile->getCountry(), ENT_QUOTES));
                $fields->addChild('ACCOUNT', 'CC');
                $fields->addChild('ACCOUNT_NUMBER', $profile->getCardNumber());
                $fields->addChild('TRANSACTION_INDICATOR', '7');
                $fields->addChild('EXPIRATION', $this->_getCcExpiration($profile->getExpirationMonth(), $profile->getExpirationYear()));
                $fields->addChild('CVV', $profile->getCardCode());
                $fields->addChild('BILLING_TRANSACTION', '2');
				$fields->addChild('FESP_IND', '9');

                $this->setTransactionForLog($request);


                if ($hasEncryption) {
                    $this->_encryptRequest($request);
                }

                $this->setTransaction($request);
            } else {
                Mage::throwException("failed to create payment profile");
            }
        } catch (Exception $e) {
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generate Xml request for updating customer profile
     * @return PlanetPayment_IpayGateway_Model_Xml_Request 
     */
    public function generateUpdateClientRequest() {
        try {
            $profile = $this->getIpayPaymentProfile();
            if ($profile) {
                $hasEncryption = $this->_hasEncryption();

                $key = $this->_getConfig('key', 'general');
                $encryption = $hasEncryption ? '1' : '0';
                $request = $this->_getRootNode();
                $transaction = $request->addChild('TRANSACTION');
                $fields = $transaction->addChild('FIELDS');
                $fields->addChild('SERVICE', 'WALLET');
                $fields->addChild('SERVICE_TYPE', 'CLIENT');
                $fields->addChild('SERVICE_SUBTYPE', 'MODIFY');
                $fields->addChild('SERVICE_FORMAT', '1010');
                $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'general'));
                $fields->addChild('PIN', $this->_getConfig('pin', 'general'));
                $fields->addChild('CLIENT_ID', $profile->getClientId());
                $fields->addChild('FIRST_NAME', htmlentities($profile->getFirstName(), ENT_QUOTES));
                $fields->addChild('LAST_NAME', htmlentities($profile->getLastName(), ENT_QUOTES));
                $fields->addChild('ADDRESS', $this->_conditionAddress($profile->getAddress()));
                $fields->addChild('CITY', htmlentities($profile->getCity(), ENT_QUOTES));
                $fields->addChild('POSTAL_CODE', $this->_conditionPostalCode($profile->getZip()));
                $fields->addChild('STATE', htmlentities($profile->getState(), ENT_QUOTES));
                $fields->addChild('COUNTRY', htmlentities($profile->getCountry(), ENT_QUOTES));
				$fields->addChild('FESP_IND', '9');

                $this->setTransactionForLog($request);


                if ($hasEncryption) {
                    $this->_encryptRequest($request);
                }

                $this->setTransaction($request);
            } else {
                Mage::throwException("failed to Update payment profile");
            }
        } catch (Exception $e) {
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generating the xml reauest for updating customer card details
     * @return PlanetPayment_IpayGateway_Model_Xml_Request 
     */
    public function generateUpdateAccountRequest() {
        try {
            $profile = $this->getIpayPaymentProfile();
            if ($profile) {
                $hasEncryption = $this->_hasEncryption();

                $key = $this->_getConfig('key', 'general');
                $encryption = $hasEncryption ? '1' : '0';
                $request = $this->_getRootNode();
                $transaction = $request->addChild('TRANSACTION');
                $fields = $transaction->addChild('FIELDS');
                $fields->addChild('SERVICE', 'WALLET');
                $fields->addChild('SERVICE_TYPE', 'ACCOUNT');
                $fields->addChild('SERVICE_SUBTYPE', 'MODIFY');
                $fields->addChild('SERVICE_FORMAT', '1010');
                $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'general'));
                $fields->addChild('PIN', $this->_getConfig('pin', 'general'));
                $fields->addChild('ACCOUNT_ID', $profile->getAccountId());
                $fields->addChild('ACCOUNT_NUMBER', $profile->getCardNumber());
                $fields->addChild('TRANSACTION_INDICATOR', '7');
                $fields->addChild('EXPIRATION', $this->_getCcExpiration($profile->getExpirationMonth(), $profile->getExpirationYear()));
                $fields->addChild('CVV', $profile->getCardCode());
				$fields->addChild('FESP_IND', '9');

                $this->setTransactionForLog($request);


                if ($hasEncryption) {
                    $this->_encryptRequest($request);
                } else {
                    $fields->addChild('PIN', $this->_getConfig('terminal_id', 'pin'));
                }

                $this->setTransaction($request);
            } else {
                Mage::throwException("failed to update payment profile");
            }
        } catch (Exception $e) {
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Deleting Customer profile from Planet payment
     * @return PlanetPayment_IpayGateway_Model_Xml_Request 
     */
    public function generateDeleteClientRequest() {
        try {
            $profile = $this->getIpayPaymentProfile();
            if ($profile) {
                $hasEncryption = $this->_hasEncryption();

                $key = $this->_getConfig('key', 'general');
                $encryption = $hasEncryption ? '1' : '0';
                $request = $this->_getRootNode();
                $transaction = $request->addChild('TRANSACTION');
                $fields = $transaction->addChild('FIELDS');
                $fields->addChild('SERVICE', 'WALLET');
                $fields->addChild('SERVICE_TYPE', 'CLIENT');
                $fields->addChild('SERVICE_SUBTYPE', 'DELETE');
                $fields->addChild('SERVICE_FORMAT', '1010');
                $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'general'));
                $fields->addChild('PIN', $this->_getConfig('pin', 'general'));
                $fields->addChild('CLIENT_ID', $profile->getClientId());
				$fields->addChild('FESP_IND', '9');

                $this->setTransactionForLog($request);

                if ($hasEncryption) {
                    $this->_encryptRequest($request);
                }

                $this->setTransaction($request);
            } else {
                Mage::throwException("failed to delete payment profile");
            }
        } catch (Exception $e) {
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Test configurations
     */
    public function generateTestConfigurationRequest() {
        try {
            $hasEncryption = $this->_hasEncryption();

            $key = $this->_getConfig('key', 'general');
            $encryption = $hasEncryption ? '1' : '0';
            $request = $this->_getRootNode();
            $transaction = $request->addChild('TRANSACTION');
            $fields = $transaction->addChild('FIELDS');
            $fields->addChild('SERVICE', 'NETWORK');
            $fields->addChild('SERVICE_TYPE', 'STATUS');
            $fields->addChild('SERVICE_SUBTYPE', 'QUERY');
            $fields->addChild('SERVICE_FORMAT', '0000');
            $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'general'));
            $fields->addChild('PIN', $this->_getConfig('pin', 'general'));
			$fields->addChild('FESP_IND', '9');

            $this->setTransactionForLog($request);

            if ($hasEncryption) {
                $this->_encryptRequest($request);
            }

            $this->setTransaction($request);
        } catch (Exception $e) {
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    public function generateRequestForCapture() {
        try {
            $payment = $this->getPayment();
            if ($payment) {
                $hasEncryption = $this->_hasEncryption();
                $billingAddress = $payment->getOrder()->getBillingAddress();
                $key = $this->_getConfig('key', 'general');
                $encryption = $hasEncryption ? '1' : '0';
                $request = $this->_getRootNode(); 
                $transaction = $request->addChild('TRANSACTION');
                $fields = $transaction->addChild('FIELDS');
                $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'general'));
                $fields->addChild('SERVICE_FORMAT', '1010');
                $paymentType = $this->_getPaymentType($payment);

                if ($paymentType == PlanetPayment_IpayGateway_Model_Ipay::PAYMENT_SERVICE_PYC) {
                    $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($this->getNativeCurrency()));
                    if ($this->getNativeCurrency() != $payment->getOrder()->getOrderCurrencyCode()) {
                        $fields->addChild('CURRENCY_INDICATOR', '2');
                    } else {
                        $fields->addChild('CURRENCY_INDICATOR', '0');
                    }
                } elseif ($paymentType == PlanetPayment_IpayGateway_Model_Ipay::PAYMENT_SERVICE_MCP) {
                    $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($payment->getOrder()->getOrderCurrencyCode()));
					if ($payment->getOrder()->getOrderCurrencyCode() != $this->getNativeCurrency()) {
						$fields->addChild('CURRENCY_INDICATOR', '1');
					} else {
						$fields->addChild('CURRENCY_INDICATOR', '0');
					}
                }

                $fields->addChild('TRANSACTION_ID', $payment->getLastTransId());
                $fields->addChild('SERVICE', 'CC');
                $fields->addChild('SERVICE_TYPE', 'DEBIT');
                $fields->addChild('SERVICE_SUBTYPE', 'CAPTURE');
                $fields->addChild('AMOUNT', $this->getAmountInStoreCurrency());
                $fields->addChild('PIN', $this->_getConfig('pin', 'general'));
				$fields->addChild('FESP_IND', '9');

                $this->setTransactionForLog($request);

                if ($hasEncryption) {
                    $this->_encryptRequest($request);
                }

                $this->setTransaction($request);
            } else {
                Mage::throwException("Unable to capture");
            }
        } catch (Exception $e) {
            Mage::throwException($e->getmessage());
        }

        return $this;
    }
    
     public function generateRequestForSale() {
        try {
            $payment = $this->getPayment();
            $quote = Mage::helper('ipay')->getQuote();
            $billingAddress = $quote->getBillingAddress();
            
            $profile = $this->_getProfile($payment->getIpayProfileId());

            $hasEncryption = $this->_hasEncryption();

            $key = $this->_getConfig('key', 'general');
            $encryption = $hasEncryption ? '1' : '0';

            $request = $this->_getRootNode();
            $transaction = $request->addChild('TRANSACTION');
            $fields = $transaction->addChild('FIELDS');
            $fields->addChild('PIN', $this->_getConfig('pin', 'general'));
            $fields->addChild('SERVICE', 'CC');
            $fields->addChild('SERVICE_TYPE', 'DEBIT');
            $fields->addChild('SERVICE_SUBTYPE', 'SALE');
            $fields->addChild('SERVICE_FORMAT', '1010');
            $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'general'));
            //If a profile selected by customer
            if ($profile->getAccountId()) {
                $fields->addChild('ACCOUNT_ID', $profile->getAccountId());
            } else {
                $fields->addChild('ACCOUNT_NUMBER', $payment->getCcNumber());
                $fields->addChild('EXPIRATION', $this->_getCcExpiration($payment->getCcExpMonth(), $payment->getCcExpYear()));
                $fields->addChild('CVV', $payment->getCcCid());
                $fields->addChild('FIRST_NAME', $billingAddress->getFirstname());
                $fields->addChild('LAST_NAME', $billingAddress->getLastname());
                $fields->addChild('ADDRESS', $this->_conditionAddress($billingAddress->getStreet(1)));
				$fields->addChild('STATE', $billingAddress->getRegionCode());
                $fields->addChild('CITY', $billingAddress->getCity());
                $fields->addChild('POSTAL_CODE', $this->_conditionPostalCode($billingAddress->getPostcode()));
            }

            $fields->addChild('AMOUNT', round($quote->getGrandTotal(),2));
            
            $paymentType = $this->_getPaymentType($payment);
            if ($paymentType == PlanetPayment_IpayGateway_Model_Ipay::PAYMENT_SERVICE_PYC) {
                $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($this->getNativeCurrency()));
                if ($this->getNativeCurrency() != $payment->getOrder()->getOrderCurrencyCode()) {
                    $fields->addChild('CURRENCY_INDICATOR', '2');
                } else {
                    $fields->addChild('CURRENCY_INDICATOR', '0');
                }
            } elseif ($paymentType == PlanetPayment_IpayGateway_Model_Ipay::PAYMENT_SERVICE_MCP) {
                $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($payment->getOrder()->getOrderCurrencyCode()));
				if ($payment->getOrder()->getOrderCurrencyCode() != $this->getNativeCurrency()) {
					$fields->addChild('CURRENCY_INDICATOR', '1');
				} else {
					$fields->addChild('CURRENCY_INDICATOR', '0');
				}
            }
            $fields->addChild('ENTRY_MODE', '3');
            $fields->addChild('TRANSACTION_INDICATOR', '7');
			$fields->addChild('FESP_IND', '9');
			$fields->addChild('USER_DATA_0', $this->_getClientName());
			
            $this->setTransactionForLog($request);
            
            if ($hasEncryption) {
                $this->_encryptRequest($request);
            }
            $this->setTransaction($request);
        } catch (Exception $e) {
            Mage::throwException($e->getmessage());
        }

        return $this;
    }
    
    public function generateRequestForVoid() {
        try {
            $payment = $this->getPayment();
            if ($payment) {
                $hasEncryption = $this->_hasEncryption();

                $key = $this->_getConfig('key', 'general');
                $encryption = $hasEncryption ? '1' : '0';
                $request = $this->_getRootNode();
                $transaction = $request->addChild('TRANSACTION');
                $fields = $transaction->addChild('FIELDS');
                $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'general'));
                $fields->addChild('SERVICE_FORMAT', '1010');
                $fields->addChild('TRANSACTION_ID', $payment->getLastTransId());
                $fields->addChild('SERVICE', 'CC');
                $fields->addChild('SERVICE_TYPE', 'DEBIT');
                $fields->addChild('SERVICE_SUBTYPE', 'VOID');
                $fields->addChild('PIN', $this->_getConfig('pin', 'general'));
				$fields->addChild('FESP_IND', '9');

                $this->setTransactionForLog($request);

                if ($hasEncryption) {
                    $this->_encryptRequest($request);
                }

                $this->setTransaction($request);
            } else {
                Mage::throwException("Unable to Void");
            }
        } catch (Exception $e) {
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    public function generateRequestForRefund() {
        try {
            $payment = $this->getPayment();
            if ($payment) {
                $hasEncryption = $this->_hasEncryption();

                $key = $this->_getConfig('key', 'general');
                $encryption = $hasEncryption ? '1' : '0';
                $request = $this->_getRootNode();
                $transaction = $request->addChild('TRANSACTION');
                $fields = $transaction->addChild('FIELDS');
                $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'general'));
                $fields->addChild('SERVICE_FORMAT', '1010');
                $paymentType = $this->_getPaymentType($payment);

                if ($paymentType == PlanetPayment_IpayGateway_Model_Ipay::PAYMENT_SERVICE_PYC) {
                    $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($this->getNativeCurrency()));
                    if ($this->getNativeCurrency() != $payment->getOrder()->getOrderCurrencyCode()) {
                        $fields->addChild('CURRENCY_INDICATOR', '2');
                    } else {
                        $fields->addChild('CURRENCY_INDICATOR', '0');
                    }
                    $fields->addChild('AMOUNT', $this->getAmount());
                } elseif ($paymentType == PlanetPayment_IpayGateway_Model_Ipay::PAYMENT_SERVICE_MCP) {
                    $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($payment->getOrder()->getOrderCurrencyCode()));
					if ($payment->getOrder()->getOrderCurrencyCode() != $this->getNativeCurrency()) {
						$fields->addChild('CURRENCY_INDICATOR', '1');
					} else {
						$fields->addChild('CURRENCY_INDICATOR', '0');
					}
                    $fields->addChild('AMOUNT', $this->getAmountInStoreCurrency()); //refund in the currency of the charge
                }

                $fields->addChild('TRANSACTION_ID', $payment->getRefundTransactionId());
                $fields->addChild('SERVICE', 'CC');
                $fields->addChild('SERVICE_TYPE', 'CREDIT');
                $fields->addChild('SERVICE_SUBTYPE', 'REFUND');
                $fields->addChild('PIN', $this->_getConfig('pin', 'general'));
				$fields->addChild('FESP_IND', '9');
                
                $this->setTransactionForLog($request);

                if ($hasEncryption) {
                    $this->_encryptRequest($request);
                }

                $this->setTransaction($request);
            } else {
                Mage::throwException("Unable to Refund");
            }
        } catch (Exception $e) {
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Sending the request to Planet Payment
     * @return PlanetPayment_IpayGateway_Model_Xml_Request 
     */
    public function send() {
        $transaction = $this->getTransaction();
        if ($transaction) {
            try {
                $isProduction = $this->_isProductionMode();
                if ($isProduction) {
                    $url = PlanetPayment_IpayGateway_Model_Ipay::GATEWAY_URL_PRODUCTION;
                } else {
                    $url = PlanetPayment_IpayGateway_Model_Ipay::GATEWAY_URL_TESTING;
                }
                //Selecting port based on the url
                $port = 86;
                if (strstr($url, 'https://')) {
                    $port = 443;
                }

                $client = new Zend_Http_Client($url, array('keepalive' => true, 'timeout' => 60));
                $client->getUri()->setPort($port);
                $client->setRawData($transaction->asXML(), 'text/xml');
                $client->setMethod(Zend_Http_Client::POST);
                $response = $client->request()->getBody();

                //Setting response to response model object
                $responseModel = $this->_getResponseModel();
                $responseModel->setIpayRequest($this);
                $responseModel->setIpayResponse($response);
                $this->setResponse($responseModel);
            } catch (Exception $e) {
                Mage::throwException($e->getmessage());
            }

            return $this;
        } else {
            Mage::throwException('invalid Transaction');
        }
    }

}
