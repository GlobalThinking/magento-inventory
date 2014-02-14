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
class GlobalThinking_Inventory_Block_Adminhtml_Inventory_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId   = 'item_id';
        $this->_blockGroup = 'globalthinking_inventory';
        $this->_controller = 'adminhtml_inventory';

        $this->_removeButton('delete');

        if ( $this->isEditable() ) {
            $this->_addButton('save_and_edit', array(
                'label'     => Mage::helper('adminhtml')->__('Save and Continue Edit'),
                'onclick'   => 'saveAndContinueEdit(\''.$this->getSaveAndContinueUrl().'\')',
                'class'     => 'save',
            ), 1);
        } else {
            $this->_removeButton('save');
            $this->_removeButton('reset');
        }
    }

    public function getSaveAndContinueUrl()
    {
        return $this->getUrl('*/*/save', array(
            '_current'   => true,
            'back'       => 'edit',
            'tab'        => '{{tab_id}}'
        ));
    }

    public function getHeaderText()
    {
        $headerText = '';
        if ( ( $product = Mage::registry('current_product') ) && $product->getId() ) {
            $headerText = Mage::helper('adminhtml')->__("Edit Inventory for \"%s\"",$product->getName());
        }
        return $this->htmlEscape($headerText);
    }

    /**
     * Does the current user have access to edit the quantities?
     *
     * @return bool
     */
    public function isEditable()
    {
        return Mage::getSingleton('admin/session')->isAllowed(GlobalThinking_Inventory_Model_Stock_Item::ACL_UPDATE);
    }
}