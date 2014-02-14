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
class GlobalThinking_Inventory_Block_Adminhtml_Inventory_Receipt_Create extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId   = 'item_id';
        $this->_blockGroup = 'globalthinking_inventory';
        $this->_controller = 'adminhtml_inventory_receipt';
        $this->_mode       = 'create';

        $this->_removeButton('delete');

        if ( $this->getRequest()->getParam('popup') ) {
            $this->_updateButton('back','onclick','window.close()');
        }

        if ( $this->canEdit() ) {
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

    public function getBackUrl()
    {
        return $this->getUrl('*/inventory_transaction');
    }

    public function getSaveAndContinueUrl()
    {
        return $this->getUrl('*/*/save', array(
            '_current'   => true,
            'back'       => 'create',
            'tab'        => '{{tab_id}}'
        ));
    }

    public function getHeaderText()
    {
        $headerText = Mage::helper('adminhtml')->__("New Inventory Receipt");
        if ( ( $receipt = Mage::registry('current_receipt') ) && $receipt->getId() ) {
            $headerText = Mage::helper('adminhtml')->__("Edit Inventory Receipt # %s",$receipt->getIncrementId());

            if ( $receipt->getName() ) {
                $headerText.= " - " . $receipt->getName();
            }
        }
        return $this->htmlEscape($headerText);
    }

    public function canEdit()
    {
        return Mage::getSingleton('admin/session')->isAllowed(GlobalThinking_Inventory_Model_Stock_Receipt::ACL_UPDATE) ||
                Mage::getSingleton('admin/session')->isAllowed(GlobalThinking_Inventory_Model_Stock_Receipt::ACL_CREATE);
    }
}