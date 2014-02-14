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
class GlobalThinking_Inventory_Adminhtml_Inventory_TransactionController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Load the layout and set the default title
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('catalog')
            ->_addBreadcrumb($this->__('Catalog'), $this->__('Catalog'))
            ->_title($this->__('Catalog'))
            ->_title($this->__('Manage Inventory'))
            ->_title($this->__('Transactions'));

        return $this;
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        $action = strtolower($this->getRequest()->getActionName());
        switch ($action) {
            case 'index':
            case 'grid':
                $aclResource = GlobalThinking_Inventory_Model_Stock_Transaction::ACL_VIEW;
                break;
            default:
                $aclResource = GlobalThinking_Inventory_Model_Stock_Transaction::ACL_MENU.'/actions';
                break;
        }
        return Mage::getSingleton('admin/session')->isAllowed($aclResource);
    }

    public function indexAction()
    {
        $this->_initAction();
        $this->renderLayout();
    }

    public function gridAction()
    {
        if ( $itemId = $this->getRequest()->getParam('item_id') ) {
            $item = Mage::getModel('cataloginventory/stock_item')->load($itemId);
            Mage::register('current_item',$item);
        }

        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('globalthinking_inventory/adminhtml_inventory_transaction_grid')->toHtml()
        );
    }
}