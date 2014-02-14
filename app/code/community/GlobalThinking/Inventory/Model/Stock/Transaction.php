<?php
/**
 * Global Thinking
 *
 * @category    Magento
 * @package     GlobalThinking_Inventory
 * @copyright   Copyright (c) 2011 Global Thinking, Inc. (http://www.globalthinking.com)
 */

/**
 * Model
 *
 * @category   Magento
 * @package    GlobalThinking_Inventory
 * @author     Franklin Strube <fstrube@globalthinking.com>
 */
class GlobalThinking_Inventory_Model_Stock_Transaction extends Mage_Core_Model_Abstract
{
    const ACL_MENU = 'catalog/globalthinking_inventory/transactions';
    const ACL_VIEW = 'catalog/globalthinking_inventory/transactions/actions/view';

    protected $_parent;

    protected function _construct()
    {
        parent::_construct();
        $this->_init('globalthinking_inventory/stock_transaction');
    }

    protected function _getParent()
    {
        if (!$this->_parent) {
            $parentId = $this->getParentId();
            $parentType = $this->getParentType();

            if ( $parentId && $parentType ) {
                if ( $parentType == 'sales/quote' ) {
                    $this->_parent = Mage::getModel($parentType)->loadByIdWithoutStore($parentId);
                } else {
                    $this->_parent = Mage::getModel($parentType)->load($parentId);
                }
            }
        }
        return $this->_parent;
    }

    public function hasParent()
    {
        $order      = $this->getOrder();
        $creditmemo = $this->getCreditmemo();
        $receipt    = $this->getReceipt();

        return ( $order && $order->getId() ) ||
            ( $creditmemo && $creditmemo->getId() ) ||
            ( $receipt && $receipt->getId() );
    }

    /**
     * Retrieve the sales order that is related to this transaction
     *
     * @return Mage_Sales_Model_Order|NULL
     */
    public function getOrder()
    {
        $parent = $this->_getParent();
        $quoteClass = Mage::app()->getConfig()->getModelClassName('sales/quote');
        $orderClass = Mage::app()->getConfig()->getModelClassName('sales/order');

        if ( $parent instanceof $quoteClass ) {
            return Mage::getModel('sales/order')->load($parent->getId(),'quote_id');
        } else if ( $parent instanceof $orderClass ) {
            return Mage::getModel('sales/order')->load($parent->getId());
        }

        return null;
    }

    /**
     * Retrieve the credit memo related to this transaction
     *
     * @return Mage_Sales_Model_Order_Creditmemo|NULL
     */
    public function getCreditmemo()
    {
        $parent = $this->_getParent();
        $creditmemoClass = Mage::app()->getConfig()->getModelClassName('sales/order_creditmemo');

        if ( $parent instanceof $creditmemoClass ) {
            return $parent;
        }

        return null;
    }

    /**
     * Retrieve the receipt related to this transaction
     *
     * @return GlobalThinking_Inventory_Model_Stock_Receipt|NULL
     */
    public function getReceipt()
    {
        $parent = $this->_getParent();
        $receiptClass = Mage::app()->getConfig()->getModelClassName('globalthinking_inventory/stock_receipt');

        if ( $parent instanceof $receiptClass ) {
            return $parent;
        }

        return null;
    }

    /**
     * Retrieve the url for the parent object
     */
    public function getParentUrl()
    {
        $url = '#';

        if ( $order = $this->getOrder() ) {
            $url = Mage::helper('adminhtml')->getUrl('*/sales_order/view',array('order_id' => $order->getId()));
        } else if ( $creditmemo = $this->getCreditmemo() ) {
            $url = Mage::helper('adminhtml')->getUrl('*/sales_creditmemo/view',array('creditmemo_id' => $creditmemo->getId()));
        } else if ( $receipt = $this->getReceipt() ) {
            $url = Mage::helper('adminhtml')->getUrl('*/inventory_receipt/view',array('receipt_id' => $receipt->getId()));
        }

        return $url;
    }
}