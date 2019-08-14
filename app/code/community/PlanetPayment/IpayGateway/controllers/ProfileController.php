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
class PlanetPayment_IpayGateway_ProfileController extends Mage_Core_Controller_Front_Action {

    public function preDispatch() {
        parent::preDispatch();
        if (!$this->_getSession()->authenticate($this) || !Mage::helper('ipay')->isEnabled()) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    /**
     * Store CC profile list action.
     */
    public function indexAction() {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }

    /**
     * Edit a payment profile.
     */
    public function editAction() {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $navigationBlock = $this->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('ipay/profile');
        }
        $this->renderLayout();
    }

    /**
     * Add a profile. 
     */
    public function newAction() {
        $this->_forward('edit');
    }

    /**
     * Save edit changes.
     */
    public function editPostAction() {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/');
        }
        $profileId = $this->getRequest()->getPost('profile_id');
        if ($this->getRequest()->isPost()) {
            $customer = $this->_getSession()->getCustomer();
            /* @var $profile PlanetPayment_IpayGateway_Model_PaymentProfile */
            $profile = Mage::getModel('ipay/profile');
            if ($profileId) {
                $profile = Mage::getModel('ipay/profile')->load($profileId);
                if ($profile->getCustomerId() != $customer->getId()) {
                    $this->_getSession()->addError($this->__('The profile does not belong to this customer.'));
                    $this->getResponse()->setRedirect(Mage::getUrl('*/*/index'));
                    return;
                }
            }
            try {
                Mage::helper('core')->copyFieldset('ipay_paymentprofile_form', 'to_paymentprofile', $this->getRequest()->getPost(), $profile);
                $profile->setCardNumberLast4(substr($profile->getCardNumber(), -4))
                        ->setCustomerId($customer->getId());
                $requestModel = Mage::getModel('ipay/xml_request')->setIpayPaymentProfile($profile)
                        ->setCustomer($customer);
                // post to Ipay
                if ($profile->getId()) {  // update
                    try {
                        $request = $requestModel->generateUpdateClientRequest()
                                ->send();
                        $response = $request->getResponse()
                                //defined in Xml_Response Model
                                ->setUpdatedPaymentProfile();
                        if ($response->isSuccess()) {
                            $request = $requestModel->generateUpdateAccountRequest()
                                    ->send();
                            $response = $request->getResponse()
                                    //defined in Xml_Response Model
                                    ->setUpdatedPaymentProfile();
                            if ($response->isSuccess()) {
                                $profile = $response->getIpayPaymentProfile();
                                $profile->setIsVisible(true)->save();
                            } else {
                                Mage::throwException("Failed to update the Profile.");
                            }
                        } else {
                            Mage::throwException("Failed to update the Profile. Message:");
                        }
                    } catch (Exception $e) {
                        $this->_getSession()
                                ->addError($e->getMessage());
                        $this->_redirectError(Mage::getUrl('*/*/edit', array('profile_id' => $profileId)));
                        return;
                    }
                } else {
                    $request = $requestModel->generateNewWalletProfileRequest()
                            ->send();
                    $response = $request->getResponse()
                            ->setPaymentProfile();
                    if ($response->isSuccess()) {
                        $profile = $response->getIpayPaymentProfile();
                        $profile->setIsVisible(true)->save();
                    } else {
                        Mage::throwException("Failed to add the Profile.");
                    }
                }
                $this->_getSession()->setProfileFormData(array())
                        ->addSuccess($this->__('The profile was successfully updated.'));
                $this->_redirectSuccess(Mage::getUrl('*/*/index'));
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->setProfileFormData($this->getRequest()->getPost())
                        ->addException($e, $e->getMessage());
                Mage::logException($e);
            } catch (Exception $e) {
                $this->_getSession()->setProfileFormData($this->getRequest()->getPost())
                        ->addException($e, $e->getMessage());
                Mage::logException($e);
            }
        }
        return $this->_redirectError(Mage::getUrl('*/*/edit', array('profile_id' => $profileId)));
    }

    /**
     * Delete a payment profile.
     */
    public function deleteAction() {
        $profileId = $this->getRequest()->getParam('profile_id');
        if ($profileId) {
            $profile = Mage::getModel('ipay/profile')->load($profileId);
            if ($profile->getCustomerId() != $this->_getSession()->getCustomer()->getId()) {
                $this->_getSession()->addError($this->__('The profile does not belong to this customer.'));
                $this->getResponse()->setRedirect(Mage::getUrl('*/*/index'));
                return;
            }

            try {
                $requestModel = Mage::getModel('ipay/xml_request')->setIpayPaymentProfile($profile)
                        ->generateDeleteClientRequest()
                        ->send();
                $response = $requestModel->getResponse();
                if ($response->isSuccess()) {
                    $profile->delete();
                    $this->_getSession()->addSuccess($this->__('The profile has been deleted.'));
                } else {
                    Mage::throwException("failed to delete your profile from Ipay. please try later.");
                }
            } catch (Exception $e) {
                $this->_getSession()->addException($e, $this->__('An error occurred while deleting the profile.'));
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Retrieve customer session object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession() {
        return Mage::getSingleton('customer/session');
    }

}
