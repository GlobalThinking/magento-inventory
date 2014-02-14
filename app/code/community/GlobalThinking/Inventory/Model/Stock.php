<?php
/**
 * Global Thinking
 *
 * @category    Magento
 * @package     GlobalThinking_Inventory
 * @copyright   Copyright (c) 2011 Global Thinking, Inc. (http://www.globalthinking.com)
 */

/**
 * Override Mage_CatalogInventory_Model_Stock
 *
 * @category   Magento
 * @package    GlobalThinking_Inventory
 * @author     Franklin Strube <fstrube@globalthinking.com>
 */
class GlobalThinking_Inventory_Model_Stock extends Mage_CatalogInventory_Model_Stock
{
    const REGISTRY_QUOTE = 'current_quote';

    /**
     * Override the default registrProductsSale() function so that we can track
     * the change in inventory.
     *
     * Subtract product qtys from stock.
     * Return array of items that require full save
     *
     * @param array $items
     * @return array
     */
    public function registerProductsSale($items)
    {
        $fullSaveItems = parent::registerProductsSale($items);
        $qtys          = $this->_prepareProductQtys($items);
        $stockInfo     = $this->_getResource()->getProductsStock($this, array_keys($qtys), true);

        foreach ($stockInfo as $itemInfo) {
            $stockItem        = Mage::getModel('cataloginventory/stock_item')->setData($itemInfo);
            $stockTransaction = Mage::getModel('globalthinking_inventory/stock_transaction')
                ->setItemId($stockItem->getId())
                ->setAdjustment(0-$qtys[$stockItem->getId()])
                ->setBalance($stockItem->getQty())
                ->setQty($stockItem->getQty());

            if ( $quoteId = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getId() ) {
                $stockTransaction->setParentType('sales/quote');
                $stockTransaction->setParentId($quoteId);
            } elseif ( $quoteId = Mage::getSingleton('checkout/session')->getQuoteId() ) {
                $stockTransaction->setParentType('sales/quote');
                $stockTransaction->setParentId($quoteId);
            } elseif ( $quote = Mage::registry(self::REGISTRY_QUOTE) ) {
                $stockTransaction->setParentType('sales/quote');
                $stockTransaction->setParentId($quote->getId());
            }

            $stockTransaction->save();
        }
        return $fullSaveItems;
    }

    /**
     * Override the default revertProductsSale() function so that we can
     * track the changes to inventory when a credit memo is processed.
     *
     * @param array $items
     * @return GlobalThinking_Inventory_Model_Stock
     */
    public function revertProductsSale($items)
    {
        parent::revertProductsSale($items);
        $qtys          = $this->_prepareProductQtys($items);
        $stockInfo     = $this->_getResource()->getProductsStock($this, array_keys($qtys), true);

        foreach ($stockInfo as $itemInfo) {
            $stockItem        = Mage::getModel('cataloginventory/stock_item')->setData($itemInfo);
            $stockTransaction = Mage::getModel('globalthinking_inventory/stock_transaction')
                ->setItemId($stockItem->getId())
                ->setAdjustment($qtys[$stockItem->getId()])
                ->setBalance($stockItem->getQty())
                ->setQty($stockItem->getQty());

            $deleted = false; // Tracks if our transaction has been deleted

            // Credit memos are tracked
            if ( $creditmemo = Mage::registry('current_creditmemo') ) {
                $stockTransaction->setParentType('sales/order_creditmemo');
                $stockTransaction->setParentId($creditmemo->getId());

            // Anything else is a failed CC order
            // TODO What about canceled orders?
            } else {
                // Figure out the current quote id (admin and frontend use different sessions)
                $checkoutSession = Mage::app()->getStore()->isAdmin()
                    ? Mage::getSingleton('adminhtml/session_quote')
                    : Mage::getSingleton('checkout/session');

                // Make sure we have a valid quote, and don't delete the wrong transactions!
                if ( $checkoutSession && $checkoutSession->getQuote() && $checkoutSession->getQuote()->getId() ) {
                    // Find all stock transactions for this quote + item combo
                    $stockTransaction->getCollection()
                        ->addFieldToFilter('parent_type', 'sales/quote')
                        ->addFieldToFilter('parent_id', $checkoutSession->getQuote()->getId())
                        ->addFieldToFilter('item_id', $stockItem->getId())
                        ->walk('delete');

                    $deleted = true;
                }
            }

            if ( !$deleted ) $stockTransaction->save();
        }
        return $this;
    }
}
