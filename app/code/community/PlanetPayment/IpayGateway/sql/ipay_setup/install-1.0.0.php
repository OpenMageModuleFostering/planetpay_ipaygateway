<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup */

$installer->startSetup();

// Sales Quote & Order entities
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'ipay_profile_id', 'int(10)');
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'ipay_profile_id', 'int(10)');

// Sales Quote & Order Payment entities
$installer->getConnection()->addColumn($installer->getTable('sales/quote_payment'), 'ipay_profile_id', 'int(10)');
$installer->getConnection()->addColumn($installer->getTable('sales/quote_payment'), 'ipay_currency_code', 'varchar(10)');
$installer->getConnection()->addColumn($installer->getTable('sales/order_payment'), 'ipay_profile_id', 'int(10)');
$installer->getConnection()->addColumn($installer->getTable('sales/order_payment'), 'ipay_currency_code', 'varchar(10)');

// Payment Profile
$installer->run("

DROP TABLE IF EXISTS `{$this->getTable('ipay/paymentProfile')}`;
CREATE TABLE `{$this->getTable('ipay/paymentProfile')}` (
	`id` int(10) unsigned NOT NULL auto_increment,
        `customer_id` int(10) unsigned NOT NULL,
	`client_id` varchar(50),
	`account_id` varchar(50),
	`is_visible` tinyint(1) default 1,
	`is_default` tinyint(1) default 0,
	`card_type` varchar(255),
	`card_number_last4` varchar(25),
	`expiration_year` smallint(4),
	`expiration_month` tinyint(2),
	`first_name` varchar(50),
	`last_name` varchar(50),
	`company` varchar(50),
	`address` varchar(60),
	`city` varchar(40),
	`state` varchar(40),
	`zip` varchar(20),
	`country` varchar(60),
	`phone_number` varchar(25),
	`fax_number` varchar(25),
	`created` datetime,
	`modified` datetime,

	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `{$this->getTable('ipay/currencyCodes')}`;
CREATE TABLE `{$this->getTable('ipay/currencyCodes')}` (
  `currency` varchar(10) NOT NULL,
  `currency_code` varchar(5) NOT NULL,
  PRIMARY KEY (`currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{$this->getTable('ipay/log')}`;
CREATE TABLE `{$this->getTable('ipay/log')}` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `request` text NULL,
  `response` text NULL,
  `create_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
