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
class PlanetPayment_IpayGateway_Block_Adminhtml_Customer_Edit_Tabs extends Mage_Adminhtml_Block_Customer_Edit_Tabs {

    /**
     * Insert the CIM tab.
     *
     * @return PlanetPayment_IpayGateway_Block_Adminhtml_Customer_Edit_Tabs
     */
    protected function _beforeToHtml() {
        if (Mage::registry('current_customer')->getId() && Mage::getStoreConfig('payment/ipay/tokenize')) {
            $this->addTab('ipay', array(
                'label' => Mage::helper('ipay')->__('Planet Payment'),
                'content' => $this->getLayout()->createBlock('ipay/adminhtml_customer_edit_tab_ipay')->toHtml(),
                'after' => 'tags'
            ));
        }
        return parent::_beforeToHtml();
    }

}
