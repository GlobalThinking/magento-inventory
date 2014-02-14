<?php
/**
 * Global Thinking
 *
 * @category    Magento
 * @package     GlobalThinking_Inventory
 * @copyright   Copyright (c) 2011 Global Thinking, Inc. (http://www.globalthinking.com)
 */

/**
 * Observer
 *
 * @category   Magento
 * @package    GlobalThinking_Inventory
 * @author     Franklin Strube <fstrube@globalthinking.com>
 */
class GlobalThinking_Inventory_Model_Observer {

    /**
     * Initialize the transaction
     *
     * @param Varien_Event_Observer $observer
     * @return GlobalThinking_Inventory_Model_Observer
     */
    public function stockItemSaveBefore( $observer )
    {
        $stockItem = $observer->getItem();

        $stockTransaction = Mage::helper('globalthinking_inventory')->getStockTransaction();
        if ( $order = Mage::registry('current_order') ) {
            $stockTransaction->setParentType('sales/order');
            $stockTransaction->setParentId($order->getId());
        }

        $beginQty = Mage::getModel('cataloginventory/stock_item')->load($stockItem->getId())->getQty();

        $stockTransaction->setItemId($stockItem->getId());
        $stockTransaction->setBeginQty(floatval($beginQty));

        return $this;
    }


    /**
     * Save the transaction to the database
     *
     * @param Varien_Event_Observer $observer
     * @return GlobalThinking_Inventory_Model_Observer
     */
    public function stockItemSaveAfter( $observer )
    {
        $stockItem = $observer->getItem();

        $stockTransaction = Mage::helper('globalthinking_inventory')->getStockTransaction();

        $adjustment = $stockItem->getQty() - $stockTransaction->getBeginQty();

        if ( $stockTransaction->getItemId() === null ) {
            $stockTransaction->setItemId($stockItem->getId());
        } else if ( $stockTransaction->getItemId() != $stockItem->getId() ) {
            Mage::log( 'Invalid transaction! '.$stockItem->getId() );
            Mage::log( $stockTransaction->getData() );
            Mage::helper('globalthinking_inventory')->clearStockTransaction();
            return $this;
        }

        if ( $adjustment != 0 ) {
            $stockTransaction->setAdjustment($adjustment);
            $stockTransaction->setBalance($stockItem->getQty());

            $stockTransaction->save();
            Mage::helper('globalthinking_inventory')->clearStockTransaction();
        }

        return $this;
    }
}