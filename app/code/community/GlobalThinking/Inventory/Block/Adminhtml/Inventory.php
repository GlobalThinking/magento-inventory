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
class GlobalThinking_Inventory_Block_Adminhtml_Inventory extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'globalthinking_inventory';
        $this->_controller = 'adminhtml_inventory';
        $this->_headerText = Mage::helper('globalthinking_inventory')->__('Inventory Items');
        $this->_removeButton('add');
    }

    public function getGridHtml()
    {
        return $this->getChildHtml('grid') . $this->getChildHtml('serializer');
    }
}