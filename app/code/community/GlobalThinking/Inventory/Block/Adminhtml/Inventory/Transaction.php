<?php
/**
 * Global Thinking
 *
 * @category    Magento
 * @package     GlobalThinking_Inventory
 * @copyright   Copyright (c) 2010 Global Thinking, Inc. (http://www.globalthinking.com)
 */

/**
 * @category   Magento
 * @package    GlobalThinking_Inventory
 * @author     Franklin Strube <fstrube@globalthinking.com>
 */
class GlobalThinking_Inventory_Block_Adminhtml_Inventory_Transaction extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'globalthinking_inventory';
        $this->_controller = 'adminhtml_inventory_transaction';
        $this->_headerText = Mage::helper('globalthinking_inventory')->__('Inventory Transactions');
        $this->_addButtonLabel = Mage::helper('globalthinking_inventory')->__('Create New Receipt');
        parent::__construct();
        if (!Mage::getSingleton('admin/session')->isAllowed(GlobalThinking_Inventory_Model_Stock_Receipt::ACL_CREATE)) {
            $this->_removeButton('add');
        }
    }

    public function getCreateUrl()
    {
        return $this->getUrl('*/inventory_receipt/new');
    }
}