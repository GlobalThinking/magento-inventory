<?php
/**
 * Global Thinking
 *
 * @category    Magento
 * @package     GlobalThinking_Inventory
 * @copyright   Copyright (c) 2011 Global Thinking, Inc. (http://www.globalthinking.com)
 */

/**
 * Default helper
 *
 * @category   Magento
 * @package    GlobalThinking_Inventory
 * @author     Franklin Strube <fstrube@globalthinking.com>
 */
class GlobalThinking_Inventory_Helper_Data extends Mage_Core_Helper_Abstract
{
    const REGISTRY_TXN_KEY = 'current_stock_transaction';
    const REGISTRY_KEY_TRANSACTION = 'current_stock_transaction';

    public function getFilterAlias() {
        $alias = array(
            'qty_to_ship'  => '(GREATEST(0,qty_ordered - qty_canceled - qty_refunded - qty_shipped))',
            'qty_on_hand' => '(qty + (SUM(qty_ordered - qty_canceled - qty_refunded - qty_shipped)))'
        );

        return $alias;
    }

    public function filterQtyToShip($productCollection, $filter) {
        /* @var $items Mage_Sales_Model_Mysql4_Order_Item_Collection */
        $items = Mage::getResourceModel('sales/order_item_collection')
            ->join('sales/order','`sales/order`.entity_id=order_id')
            ->addFieldToFilter('`sales/order`.status',array('nin'=>array('canceled','closed')))
            ->addExpressionFieldToSelect('qty_to_ship','(SUM( {{qty_to_ship}} ))',$this->getFilterAlias());

        // Group by product ID
        $items->getSelect()->group('product_id');

        // Find all product IDs with the appropriate qty_to_ship
        if ( !@empty($filter['from']) ) $items->getSelect()->having('qty_to_ship >= ?', $filter['from']);
        if ( !@empty($filter['to']) ) $items->getSelect()->having('qty_to_ship <= ?', $filter['to']);

        // Filter out (by product id) whatever doesn't match the filter data
        $productCollection->addFieldToFilter('entity_id', $items->getColumnValues('product_id'));

        // Return the helper
        return $this;
    }

    public function filterQtyOnHand($productCollection, $filter) {
        /* @var $items Mage_Sales_Model_Mysql4_Order_Item_Collection */
        $items = Mage::getResourceModel('sales/order_item_collection')
            ->join('sales/order','`sales/order`.entity_id=order_id')
            ->join('cataloginventory/stock_item','`cataloginventory/stock_item`.product_id=`main_table`.product_id',array('qty_on_sale'=>'qty'))
            ->addFieldToFilter('`sales/order`.status',array('nin'=>array('canceled','closed')))
            ->addExpressionFieldToSelect('qty_to_ship','(SUM({{qty_to_ship}}))',$this->getFilterAlias())
            ->addExpressionFieldToSelect('qty_on_hand','( qty + SUM({{qty_to_ship}}) )',$this->getFilterAlias());

        Mage::log("BEFORE: " . $items->getSelect());

        // Group by product ID
        $items->getSelect()->group('product_id');

        // Find all product IDs with the appropriate qty_to_ship
        if ( !@empty($filter['from']) ) $items->getSelect()->having('qty_on_hand >= ?', $filter['from']);
        if ( !@empty($filter['to']) ) $items->getSelect()->having('qty_on_hand <= ?', $filter['to']);

        // Filter out (by product id) whatever doesn't match the filter data
        $productCollection->addFieldToFilter('entity_id', $items->getColumnValues('product_id'));

        Mage::log("AFTER: " . $items->getSelect());
        // Return the helper
        return $this;
    }

    /**
     * Retrieves the current stock transaction from the registry, or creates a new one.
     *
     * @return GlobalThinking_Inventory_Model_Stock_Transaction
     */
    public function getStockTransaction($stockItem = null) {
        $stockItemClass = Mage::app()->getConfig()->getModelClassName('cataloginventory/stock_item');
        $productClass = Mage::app()->getConfig()->getModelClassName('catalog/product');

//      if ( !Mage::registry(self::REGISTRY_TXN_KEY) ) {
//          $this->_initTransactionRegistry();
//      }
//
//      if ( $stockItem instanceof $productClass ) {
//          $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($stockItem);
//          $transaction = Mage::registry(self::REGISTRY_TXN_KEY)->getItemByColumnValue('item_id',$stockItem->getId());
//      } elseif ( $stockItem instanceof $stockItemClass ) {
//          $transaction = Mage::registry(self::REGISTRY_TXN_KEY)->getItemByColumnValue('item_id',$stockItem->getId());
//      } elseif ( is_numeric($stockItem) ) {
//          $transaction = Mage::registry(self::REGISTRY_TXN_KEY)->getItemByColumnValue('item_id',$stockItem);
//      }

        $transaction = Mage::registry(self::REGISTRY_KEY_TRANSACTION);
        if ( !$transaction ) {
            $transaction = Mage::getModel('globalthinking_inventory/stock_transaction');
//          Mage::registry(self::REGISTRY_TXN_KEY)->addItem($transaction);
            Mage::register(self::REGISTRY_KEY_TRANSACTION,$transaction);
        }

        return $transaction;
    }

    /**
     * Clears the stock transaction from the registry
     *
     * @return GlobalThinking_Inventory_Model_Stock_Transaction
     */
    public function clearStockTransaction() {
//      $stockItemClass = Mage::app()->getConfig()->getModelClassName('cataloginventory/stock_item');
//      $productClass = Mage::app()->getConfig()->getModelClassName('catalog/product');
//
//      if ( !Mage::registry(self::REGISTRY_TXN_KEY) ) {
//          $this->_initTransactionRegistry();
//          return Mage::registry(self::REGISTRY_TXN_KEY);
//      }
//
//      return Mage::registry(self::REGISTRY_TXN_KEY)->removeItemByKey($id);
        Mage::unregister(self::REGISTRY_KEY_TRANSACTION);
    }

    /**
     * Initialize the stock transaction collection
     */
    protected function _initTransactionRegistry() {
        $collection = Mage::getResourceModel('globalthinking_inventory/stock_transaction_collection')->clear();
        Mage::register(self::REGISTRY_TXN_KEY,$collection);
    }
}