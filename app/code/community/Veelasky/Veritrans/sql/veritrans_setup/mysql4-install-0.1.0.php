<?php
/**
 * Veritrans Setup
 *
 * @package		Magento
 * @author		veelasky <riefky.alhuraibi@gmail.com>
 * @version		0.1.0
 *
 * @see			https://github.com/veritrans/
 */

$installer = $this;

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS `{$this->getTable('veritrans')}`;
CREATE TABLE `{$this->getTable('veritrans')}` (
`veritrans_id` int(11) NOT NULL auto_increment,
`session_id` varchar(50) NOT NULL default '',
`order_id` varchar(100) NOT NULL default '',
`token_merchant` varchar(255) NOT NULL default '',
`amount` decimal(12,4) NOT NULL default '0',
`status` varchar(50) NOT NULL default '',
`start_time` datetime NOT NULL default '0000-00-00 00:00:00',
`finish_time` datetime NOT NULL default '0000-00-00 00:00:00',
PRIMARY KEY (`veritrans_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

");

$installer->endSetup();