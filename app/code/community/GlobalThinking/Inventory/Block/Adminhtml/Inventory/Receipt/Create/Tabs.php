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
class GlobalThinking_Inventory_Block_Adminhtml_Inventory_Receipt_Create_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('inventory_receipt_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('adminhtml')->__('Receipt Details'));
    }

    protected function _beforeToHtml()
    {
        $receipt = Mage::registry('current_receipt');

        $this->addTab('information', array(
            'label'     => Mage::helper('adminhtml')->__('Information'),
            'title'     => Mage::helper('adminhtml')->__('Information'),
            'content'   => $this->getLayout()->createBlock('globalthinking_inventory/adminhtml_inventory_receipt_create_tab_information')->toHtml(),
        ));

        $this->addTab('products', array(
            'label'     => Mage::helper('adminhtml')->__('Products'),
            'title'     => Mage::helper('adminhtml')->__('Products'),
            'url'       => $this->getUrl('*/*/chooser',array(
                'use_massaction' => true,
                'receipt_id'     => $receipt->getId(),
                'readonly'       => (bool) $receipt->getId())
            ),
            'class'     => 'ajax'
        ));

        $activeTab = str_replace("{$this->getId()}_",'',$this->getRequest()->getParam('tab'));
        if ($activeTab) $this->setActiveTab($activeTab);

        return parent::_beforeToHtml();
    }
}