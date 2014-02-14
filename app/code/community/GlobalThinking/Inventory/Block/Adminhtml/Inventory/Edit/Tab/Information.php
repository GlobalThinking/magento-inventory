<?php
/**
 * Global Thinking
 *
 * @category    Magento
 * @package     GlobalThinking_Inventory
 * @copyright   Copyright (c) 2010 Global Thinking, Inc. (http://www.globalthinking.com)
 */

/**
 * Form block for a specific inventory item
 *
 * @category   Magento
 * @package    GlobalThinking_Inventory
 * @author     Franklin Strube <fstrube@globalthinking.com>
 */
class GlobalThinking_Inventory_Block_Adminhtml_Inventory_Edit_Tab_Information extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        // Prep the fieldsets
        $productFieldset = $form->addFieldset('product_info', array(
            'legend'=>Mage::helper('adminhtml')->__('Product Information')
        ));

        $stockFieldset = $form->addFieldset('stock_info', array(
            'legend'=>Mage::helper('adminhtml')->__('Stock Information')
        ));

        // Prep the form data
        $data = array();
        if ( $product = Mage::registry('current_product') ) {
            $data+= $product->getData();
        }

        if ( $item = Mage::registry('current_item') ) {
            $data+= $item->getData();
            $data['qty_to_ship'] = $item->getQtyToShip();
            $data['qty_on_hand'] = $item->getQty() + $item->getQtyToShip();
        }

        if ( Mage::getSingleton('adminhtml/session')->getItemData() ) {
            $data+= Mage::getSingleton('adminhtml/session')->getItemData();
            Mage::getSingleton('adminhtml/session')->setItemData(null);
        }

        // Add fields to the product fieldset
        $productFieldset->addField('sku', 'label', array(
            'label'     => Mage::helper('adminhtml')->__('Sku'),
            'name'      => 'sku'
        ));

        $productFieldset->addField('name', 'label', array(
            'label'     => Mage::helper('adminhtml')->__('Name'),
            'name'      => 'name'
        ));

        if ( !empty($data['short_description']) ) {
            $productFieldset->addField('short_description', 'label', array(
                'label'     => Mage::helper('adminhtml')->__('Short Description'),
                'name'      => 'short_description'
            ));
        }

        if ( !empty($data['long_description']) ) {
            $productFieldset->addField('long_description', 'label', array(
                'label'     => Mage::helper('adminhtml')->__('Long Description'),
                'name'      => 'long_description'
            ));
        }

        // Add fields to the stock fieldset
        $stockFieldset->addField('qty', 'label', array(
            'label'     => Mage::helper('adminhtml')->__('Qty on Sale'),
            'name'      => 'qty_on_sale'
        ));

        $stockFieldset->addField('qty_to_ship', 'label', array(
            'label'     => Mage::helper('adminhtml')->__('Qty to Ship'),
            'name'      => 'qty_to_ship'
        ));

        $stockFieldset->addField('qty_on_hand', ($this->isEditable() ? 'text' : 'label'), array(
            'label'     => Mage::helper('adminhtml')->__('Qty on Hand'),
            'name'      => 'qty_on_hand'
        ));

        $form->setValues($data);

        $this->setForm($form);

        return parent::_prepareForm();
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