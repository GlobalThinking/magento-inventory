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
class GlobalThinking_Inventory_Block_Adminhtml_Inventory_Receipt_Create_Search extends Mage_Adminhtml_Block_Catalog_Product_Widget_Chooser
{
    protected function _prepareCollection()
    {
        /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId(0)
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('short_description')
            ->addAttributeToSelect('long_description');

        // Category filter
        if ($categoryId = $this->getCategoryId()) {
            $category = Mage::getModel('catalog/category')->load($categoryId);
            if ($category->getId()) {
                // $collection->addCategoryFilter($category);
                $productIds = $category->getProductsPosition();
                $productIds = array_keys($productIds);
                if (empty($productIds)) {
                    $productIds = 0;
                }
                $collection->addFieldToFilter('entity_id', array('in' => $productIds));
            }
        }

        // Product type filter
        if ($productTypeId = $this->getProductTypeId()) {
            $collection->addAttributeToFilter('type_id', $productTypeId);
        }

        // If the chooser is "readonly", pull in the qty_received and filter out other products
        if ( $this->getReadonly() && $this->getReceiptId() ) {

            $receipt      = Mage::getModel('globalthinking_inventory/stock_receipt')->load($this->getReceiptId());
            $itemIds      = $receipt->getItemCollection()->getColumnValues('item_id');
            $productIds   = array();

            foreach( $itemIds as $itemId ) {
                /* @var $item Mage_Cataloginventory_Model_Stock_Item */
                $productIds[]    = Mage::getModel('cataloginventory/stock_item')->load($itemId)->getProductId();
            }

            $collection->addFieldToFilter('entity_id',array('in'=>$productIds));

            $condition = array(
                'parent_type' => 'globalthinking_inventory/stock_receipt',
                'parent_id'   => $this->getReceiptId()
            );

            $collection
                ->joinField('item_id','cataloginventory/stock_item','item_id','product_id=entity_id')
                ->joinField('qty_received','globalthinking_inventory/stock_transaction','adjustment','item_id=item_id',$condition);
        }

        $this->setCollection($collection);

        // Skip the parent and go right to the grid
        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        parent::_prepareColumns();

        unset($this->_columns['entity_id']);

        $this->addColumn('short_description', array(
            'header'     => $this->__('Short Description'),
            'index'      => 'short_description'
        ));

        if ( $this->getReadonly() && $this->getReceiptId() ) {
            $this->addColumn('qty_received', array(
                'header'     => $this->__('Qty Received'),
                'index'       => 'qty_received',
                'type'       => 'number',
                'width'      => '80px'
            ));
        } else {
            $this->addColumn('qty_received', array(
                'filter'     => false,
                'header'     => $this->__('Qty Received'),
                'name'       => 'qty_received',
                'type'       => 'input',
                'width'      => '80px'
            ));
        }

        return Mage_Adminhtml_Block_Widget_Grid::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/chooser', array(
            'products_grid' => true,
            '_current' => true,
            'uniq_id' => $this->getId(),
            'use_massaction' => $this->getUseMassaction(),
            'product_type_id' => $this->getProductTypeId()
        ));
    }
}
