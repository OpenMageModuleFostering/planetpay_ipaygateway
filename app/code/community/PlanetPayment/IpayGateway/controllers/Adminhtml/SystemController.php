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
class PlanetPayment_IpayGateway_Adminhtml_SystemController extends Mage_Adminhtml_Controller_Action {

    /**
     * Purges all Ipay customer and profile data from Magento.
     * Typically this is run after switching from a test account to a production account.
     */
    public function purgeAction() {
        $profiles = Mage::getModel('ipay/profile')->getCollection();
        /* @var $resource PlanetPayment_IpayGateway_Model_Profile */
        try {
            $errors = array();
            if (count($profiles)) {
                foreach ($profiles as $profile) {
                    $requestModel = Mage::getModel('ipay/xml_request')->setIpayPaymentProfile($profile)
                            ->generateDeleteClientRequest()
                            ->send();
                    $response = $requestModel->getResponse();
                    if ($response->isSuccess()) {
                        $profile->delete();
                    } else {
                        $errors[$profile->getClientId()] = $response->getMessage();
                    }
                }

                if (count($errors)) {
                    $message = "Some of the profiles couldn't delete.<br/>";
                    foreach ($errors as $accountId => $reason) {
                        $message .= "Account ID: $accountId, Message: $reason <br/>";
                    }
                    Mage::getSingleton('adminhtml/session')->addError($message);
                } else {
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('ipay')->__('Ipay Profiles has been successfully purged.'));
                }
            } else {
                Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('ipay')->__('No Profiles Found.'));
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirectReferer();
    }

    /**
     * Exports the logs into a text file
     */
    public function exportAction() {
        try {
            $logModel = Mage::getmodel('ipay/log');
            $logs = $logModel->getCollection();
            if (count($logs)) {
                $content = "CREATE TABLE IF NOT EXISTS `" . $logModel->getResource()->getTable('ipay/log') . "` 
                    (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `request` text NULL,
  `response` text NULL,
  `create_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
                $content .= "INSERT INTO `" . $logModel->getResource()->getTable('ipay/log') . "` (`request`, `response` , `create_date`) VALUES ";
                $i = 0;
                foreach ($logs as $log) {
                    $content .= $i != 0 ? ", " : "";
                    $content .= "('" . addslashes($log->getRequest()) . "','" . addslashes($log->getResponse()) . "','" . $log->getCreateDate() . "')";
                    $i++;
                }
                $this->_prepareDownloadResponse("LogExport.sql", $content);
                return;
            } else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('ipay')->__('Log is empty'));
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirectReferer();
    }

    public function testAction() {
        $requestModel = Mage::getModel('ipay/xml_request')->generateTestConfigurationRequest()
                ->send();
        $response = $requestModel->getResponse();
        if ($response->isSuccess()) {
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('ipay')->__('Configurations tested successfully.'));
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('ipay')->__('Error in configuration.</br> Message:'.$response->getMessage()));
        }
        $this->_redirectReferer();
    }

    /**
     * ACL check.
     *
     * @return bool
     */
    protected function _isAllowed() {
        $actionName = $this->getRequest()->getActionName();
        return Mage::getSingleton('admin/session')->isAllowed('ipay/system/' . $actionName);
    }

}
