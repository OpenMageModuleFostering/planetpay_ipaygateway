<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup */

$installer->startSetup();

// Sales Quote & Order entities
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'ipay_exchange_rate', 'float(15,6)');
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'ipay_markup', 'varchar(15)');
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'ipay_service_used', 'varchar(10)');

$installer->endSetup();
