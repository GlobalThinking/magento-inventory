<?php
/**
 * Global Thinking
 *
 * @category    Magento
 * @package     GlobalThinking_Inventory
 * @copyright   Copyright (c) 2011 Global Thinking, Inc. (http://www.globalthinking.com)
 */

$installer = $this;
$installer->startSetup();

/**
 * globalthinking_inventory/stock_transaction table definition
 */
$installer->run("
    DROP TABLE IF EXISTS `{$installer->getTable('globalthinking_inventory/stock_transaction')}`;
    CREATE TABLE `{$installer->getTable('globalthinking_inventory/stock_transaction')}` (
        `entity_id` int(11) unsigned NOT NULL auto_increment,
        `item_id` int UNSIGNED NOT NULL,
        `parent_type` varchar(100) NOT NULL,
        `parent_id` int(11) unsigned,
        `adjustment` decimal(12,4),
        `balance` decimal(12,4),
        `extra` text,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`entity_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->getConnection()->addKey($installer->getTable('globalthinking_inventory/stock_transaction'),'FK_GT_STOCK_TRANSACTION_ITEM','item_id');

$installer
    ->getConnection()
    ->addConstraint(
        'FK_GT_STOCK_TRANSACTION_ITEM',
        $installer->getTable('globalthinking_inventory/stock_transaction'),
        'item_id',
        $installer->getTable('cataloginventory/stock_item'),
        'item_id',
        'cascade',
        'cascade'
);
?>