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
class PlanetPayment_IpayGateway_Model_System_Config_Payment_Cctype extends Mage_Adminhtml_Model_System_Config_Source_Payment_Cctype {

    //Allowed CC TYPES
    //Add New CC TYPE to the array inorder to show that in the select box
    private $__allowedCcTypes = array(
        'AE',
        'VI',
        'DI',
        'MC',
        'JCB'
    );
    
    private $__additionalCcTypes = array(
        array(
            'value' => 'DIN',
            'label' => 'Diners'
        ),
//      array(
//          'value' => 'CUP',
//          'label' => 'CUP'
//      )
    );

    public function toOptionArray() {
        $options = parent::toOptionArray();
        $allowedOptions = array();
        foreach ($options as $option) {
            if (isset($option['value'])) {
                if (in_array($option['value'], $this->__allowedCcTypes)) {
                    array_push($allowedOptions, $option);
                }
            }
        }
        //Adding Additional CCTypes to Options
        $allowedOptions = array_merge($allowedOptions, $this->__additionalCcTypes);
        return $allowedOptions;
    }
    
    public function getAdditionalCcTypes() {
        $addtional = array();
        foreach($this->__additionalCcTypes as $type) {
            $addtional[$type['value']] = $type['label'];
        }
        
        return $addtional;
    }

}
