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
class PlanetPayment_IpayGateway_Helper_Error extends Mage_Core_Helper_Abstract {

    private $__arcs = array(
        'code' => 'Definition',
        '00' => 'Approval',
        '1' => 'See RESPONSE_TEXT for issuer phone number',
        '2' => 'See RESPONSE_TEXT for issuer phone number',
        '3' => 'Invalid merchant ID',
        '4' => 'Pick up card',
        '05' => 'Do not honor',
        '6' => 'General error',
        '7' => 'Do not honor',
        '8' => 'Honor with customer ID',
        '10' => 'Partial approval for the authorized amount returned',
        '11' => 'VIP approval',
        '12' => 'Invalid transaction',
        '13' => 'Invalid transaction amount',
        '14' => 'Invalid card number',
        '15' => 'No such issuer',
        '17' => 'Customer cancellation',
        '19' => 'Re-enter transaction',
        '21' => 'Unable to back out transaction',
        '25' => 'Unable to locate record in file, or account number is missing from inquiry',
        '27' => 'Issuer File Update field edit error',
        '28' => 'Temporarily unavailable',
        '30' => 'Format error',
        '32' => 'Partial reversal',
        '40' => 'Requested function not supported',
        '41' => 'Pickup card—lost',
        '43' => 'Pickup card—stolen',
        '51' => 'Insufficient funds',
        '52' => 'No checking account',
        '53' => 'No savings account',
        '54' => 'Expired card',
        '55' => 'Incorrect PIN',
        '57' => 'Transaction not permitted—card',
        '58' => 'Transaction not permitted—terminal',
        '59' => 'Suspected fraud',
        '61' => 'Exceeds withdrawal limit',
        '62' => 'Invalid service code, restricted',
        '63' => 'Security violation',
        '65' => 'Activity limit exceeded',
        '68' => 'Response received late',
        '75' => 'PIN tries exceeded',
        '76' => 'Unable to locate',
        '77' => 'Inconsistent data, rev. or repeat',
        '78' => 'No account',
        '79' => 'Already reversed',
        '80' => 'Invalid date',
        '81' => 'Cryptographic error',
        '82' => 'CVV data incorrect',
        '83' => 'Cannot verify PIN',
        '84' => 'Invalid authorization life cycle',
        '85' => 'No reason to decline',
        '86' => 'Cannot verify PIN',
        '87' => 'Network unavailable',
        '91' => 'Issuer unavailable',
        '92' => 'Destination not found',
        '93' => 'Violation, cannot complete',
        '94' => 'Duplicate transmission detected',
        '96' => 'Re-send, system error',
        'AX' => 'Amount exceeds either the minimum or maximum allowed amount',
        'B1' => 'Surcharge amount not permitted on Visa cards or EBT food stamps',
        'ER' => 'Error—see MRC response',
        'N0' => 'Force STIP',
        'N3' => 'Cash back service not available',
        'N4' => 'Exceeds issuer withdrawal limit',
        'N7' => 'CVV2 value supplied is invalid',
        'P2' => 'Invalid biller information',
        'P5' => 'PIN charge/unblock declined',
        'P6' => 'Unsafe PIN',
        'Q1' => 'Card authentication failed',
        'R0' => 'Customer requested stop of specific recurring payment.',
        'R1' => 'Customer requested stop of all recurring payments from specific merchant.',
        'R3' => 'Revocation of All Authorizations Order',
        'SD' => 'Transaction is declined by the Gateway based on merchant’s settings for ACCOUNT_VALIDATION and CONSUMER_VALIDATION',
        'TO' => 'Re-submit',
        'XA' => 'Forward to issuer',
        'XD' => 'Forward to issuer',
        'Z3' => 'Unable to go online, declined.',);
    
    private $__mrcs = array(
        'Code' => 'Definition',
        '0' => 'Payment server validation approved',
        'AE' => 'AUTH_EXPIRED authorizations are held for 10 days and then released',
        'AR' => 'ACCOUNT_NUMBER BIN is not setup to process',
        'AX' => 'Transaction amount value requirements exceeded, see response text for details',
        'CD' => 'Commercial data already associated',
        'CF' => 'Credit refused, must have a relevant sale in order to process credit',
        'DC' => 'Data conflict',
        'DF' => 'Data-Frequency mismatch. The combination of fields has violated the Gateway frequency logic.',
        'DR' => 'Delete refused—data integrity enforcement',
        'IB' => 'Invalid base64 encoding',
        'IC' => 'Missing/invalid company key',
        'ID' => 'Missing/invalid transaction data',
        'IE' => 'Invalid encryption',
        'IK' => 'Invalid key (See RESPONSE_TEXT for the invalid key)',
        'IS' => 'Inactive service',
        'IT' => 'Invalid XML transmission format',
        'IX' => 'Invalid XML transaction format',
        'IY' => 'Invalid type attribute',
        'IZ' => 'Invalid compression (future use)',
        'LM' => 'Field LAST_FOUR did not match last four digits of cardholder’s acct. no. contained in TRACK_DATA',
        'MK' => 'Missing key (See RESPONSE_TEXT for the missing key)',
        'MY' => 'Missing type attribute',
        'NF' => 'Transaction not found',
        'NM' => 'No data mapping; please call Planet Payment',
        'NS' => 'Transaction not settled',
        'NX' => 'No XML "FIELDS" node present',
        'SE' => 'System error; please call Planet Payment',
        'SU' => 'System unavailable, retry',
        'TC' => 'Transaction already captured',
        'TD' => 'Transaction already deleted',
        'TR' => 'Transaction already reversed',
        'TS' => 'Transaction already settled',
        'TV' => 'Transaction already voided',
        'UP' => 'Unable to process at this time, retry',
        'VR' => 'VOID_REFUSED Merchants receiving a decline for a sale transaction will not be able to void it.',
        'XE' => 'Currency conversion error; please call Planet Payment',);

    public function getErrorMessage($code, $type = 'arc') {
        if ($type == 'arc') {
            if (isset($this->__arcs[$code])) {
                return $this->__arcs[$code];
            }
        } else if (isset($this->__mrcs[$code])) {
            return $this->__mrcs[$code];
        } else {
            return '';
        }
    }

}