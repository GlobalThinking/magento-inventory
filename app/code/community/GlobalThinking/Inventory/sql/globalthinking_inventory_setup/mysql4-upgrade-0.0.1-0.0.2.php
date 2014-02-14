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
 * globalthinking_inventory/stock_receipt table definition
 */
$installer->run("
    DROP TABLE IF EXISTS `{$installer->getTable('globalthinking_inventory/stock_receipt')}`;
    CREATE TABLE `{$installer->getTable('globalthinking_inventory/stock_receipt')}` (
        `receipt_id` int(11) unsigned NOT NULL auto_increment,
        `increment_id` varchar(50),
        `name` varchar(255),
        `comment` text,
        `reference_type` varchar(255),
        `reference_id` varchar(255),
        `extra` text,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (`receipt_id`),
        UNIQUE KEY `UNQ_INCREMENT_ID` (`increment_id`),
        KEY `IDX_REFERENCE_TYPE` (`reference_type`),
        KEY `IDX_REFERENCE_ID` (`reference_id`),
        KEY `IDX_CREATED_AT` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$entityTypeCode = 'stock_receipt';
$entityTypeData = array(
    'entity_model'               => 'globalthinking_inventory/stock_receipt',
    'entity_table'               => 'globalthinking_inventory/stock_receipt',
    'increment_model'            => 'eav/entity_increment_numeric',
    'increment_per_store'        => 0,
    'default_attribute_set_id'   => 0
);
$eavSetup = new Mage_Eav_Model_Entity_Setup('core_setup');
$eavSetup->addEntityType($entityTypeCode,$entityTypeData);
$eavSetup->updateEntityType($entityTypeCode,$entityTypeData);

// Set increment ids for each saved cart
$receipts = Mage::getResourceModel('globalthinking_inventory/stock_receipt_collection');

foreach ( $receipts as $receipt ) {
    if ( !$receipt->getIncrementId() ) {
        $incrementId = Mage::getSingleton('eav/config')
            ->getEntityType('stock_receipt')
            ->fetchNewIncrementId();
        $cart->setIncrementId($incrementId)->save();
    }
}