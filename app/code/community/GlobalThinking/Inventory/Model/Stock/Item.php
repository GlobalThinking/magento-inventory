<?php
/**
 * Global Thinking
 *
 * @category    Magento
 * @package     GlobalThinking_Inventory
 * @copyright   Copyright (c) 2011 Global Thinking, Inc. (http://www.globalthinking.com)
 */

/**
 * Override Mage_CatalogInventory_Model_Stock_Item
 *
 * @category   Magento
 * @package    GlobalThinking_Inventory
 * @author     Franklin Strube <fstrube@globalthinking.com>
 */
class GlobalThinking_Inventory_Model_Stock_Item extends Mage_CatalogInventory_Model_Stock_Item
{
    protected $_qtyToShip;
    protected $_qtyOnHand;

    const ACL_MENU    = 'catalog/globalthinking_inventory/items';
    const ACL_VIEW    = 'catalog/globalthinking_inventory/items/actions/view';
    const ACL_UPDATE  = 'catalog/globalthinking_inventory/items/actions/update';

    /**
     * Fix to dispatch proper events for the stock item model
     */
    protected function _beforeSave()
    {
        // Calculate qty on sale to save in database
        if ( ($qtyOnHand = $this->getQtyOnHand()) !== null ) {
            $this->setQty($qtyOnHand - $this->getQtyToShip());
        }

        Mage::dispatchEvent('model_save_before', array('object'=>$this));
        Mage::dispatchEvent($this->_eventPrefix.'_save_before', $this->_getEventData());
        return parent::_beforeSave();
    }

    public function getQtyToShip()
    {
        if ( $this->_qtyToShip === null ) {
            $alias = array(
                'qty_ordered'  => 'qty_ordered',
                'qty_canceled' => 'qty_canceled',
                'qty_refunded' => 'qty_refunded',
                'qty_shipped'  => 'qty_shipped'
            );

            // Calculate qty_to_ship from order_items
            /* @var $items Mage_Sales_Model_Mysql4_Order_Item_Collection */
            $items = Mage::getResourceModel('sales/order_item_collection')
                ->addFieldToFilter('product_id', $this->getProductId())
                ->addExpressionFieldToSelect('qty_to_ship','(IF( {{qty_ordered}} - {{qty_canceled}} - {{qty_refunded}} - {{qty_shipped}} > 0, {{qty_ordered}} - {{qty_canceled}} - {{qty_refunded}} - {{qty_shipped}}, 0))',$alias);

            // Don't count canceled orders
            $canceledQuery = Mage::getSingleton('core/resource')->getConnection('core_read')->select()
                ->from('sales_flat_order', 'entity_id')
                ->where('status = ?', 'canceled');
            $items->addFieldToFilter('order_id', array('nin' => $canceledQuery));

            $this->_qtyToShip = array_sum($items->getColumnValues('qty_to_ship'));
        }

        return $this->_qtyToShip;
    }

    public function getData($key='',$index=null)
    {
        switch($key) {
            case 'qty_on_sale':
                return $this->getQty();
                break;
            case 'qty_to_ship':
                return $this->getQtyToShip();
                break;
        }
        return parent::getData($key,$index);
    }

    public function getQtyAtDate($date)
    {
        $transaction = Mage::getResourceModel('globalthinking_inventory/stock_transaction');
        $select = $this->_getResource()->getReadConnection()->select()
            ->from($transaction->getMainTable(), array("balance"))
            ->where('item_id = ?', $this->getId())
            ->where('created_at <= ?', $date)
            ->order('created_at DESC');
        $qty = $this->_getResource()->getReadConnection()->fetchOne($select);

        // Get the qty from a newer balance
        if ( !is_numeric($qty) ) {
            $select = $this->_getResource()->getReadConnection()->select()
                ->from($transaction->getMainTable(), array("balance","adjustment"))
                ->where('item_id = ?', $this->getId())
                ->where('created_at >= ?', $date)
                ->order('created_at ASC');
            $row = $this->_getResource()->getReadConnection()->fetchRow($select);
            $qty = empty($row) ? null : $row['balance'] - $row['adjustment'];
        }

        // Get the current qty if there are no transactions for this item
        if ( !is_numeric($qty) ) {
            $qty = $this->getQty();
        }

        return $qty;
    }
}
