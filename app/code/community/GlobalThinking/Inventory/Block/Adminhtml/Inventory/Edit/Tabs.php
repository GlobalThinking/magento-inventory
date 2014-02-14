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
class GlobalThinking_Inventory_Block_Adminhtml_Inventory_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('inventory_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('adminhtml')->__('Inventory Details'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('information', array(
            'label'     => Mage::helper('adminhtml')->__('Information'),
            'title'     => Mage::helper('adminhtml')->__('Information'),
            'content'   => $this->getLayout()->createBlock('globalthinking_inventory/adminhtml_inventory_edit_tab_information')->toHtml(),
        ));

        if ( $this->canViewTransactions() ) {
            $this->addTab('transactions', array(
                'label'     => Mage::helper('adminhtml')->__('Transactions'),
                'title'     => Mage::helper('adminhtml')->__('Transactions'),
                'url'       => $this->getUrl('*/inventory_transaction/grid', array('_current'=>true)),
                'class'     => 'ajax'
            ));
        }


        $activeTab = str_replace("{$this->getId()}_",'',$this->getRequest()->getParam('tab'));
        if ($activeTab) $this->setActiveTab($activeTab);

        return parent::_beforeToHtml();
    }

    public function canViewTransactions()
    {
        return Mage::getSingleton('admin/session')->isAllowed(GlobalThinking_Inventory_Model_Stock_Transaction::ACL_VIEW);
    }
}