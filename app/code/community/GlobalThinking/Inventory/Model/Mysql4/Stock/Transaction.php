<?php
/**
 * Global Thinking
 *
 * @category    Magento
 * @package     GlobalThinking_Inventory
 * @copyright   Copyright (c) 2011 Global Thinking, Inc. (http://www.globalthinking.com)
 */

/**
 * Model resource
 *
 * @category   Magento
 * @package    GlobalThinking_Inventory
 * @author     Franklin Strube <fstrube@globalthinking.com>
 */
class GlobalThinking_Inventory_Model_Mysql4_Stock_Transaction extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('globalthinking_inventory/stock_transaction', 'entity_id');
    }
}