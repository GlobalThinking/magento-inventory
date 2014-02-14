<?php
/**
 * Global Thinking
 *
 * @category    Magento
 * @package     GlobalThinking_Inventory
 * @copyright   Copyright (c) 2011 Global Thinking, Inc. (http://www.globalthinking.com)
 */

/**
 * Collection resource
 *
 * @category   Magento
 * @package    GlobalThinking_Inventory
 * @author     Franklin Strube <fstrube@globalthinking.com>
 */
class GlobalThinking_Inventory_Model_Mysql4_Stock_Transaction_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('globalthinking_inventory/stock_transaction');
    }

    public function addAttributeToSelect($code, $alias = false)
    {
        $alias = $alias ? $alias : $code;

        /* @var $attribute Mage_Catalog_Model_Entity_Attribute */
        $attribute = Mage::getModel('catalog/entity_attribute')->loadByCode('catalog_product',$code);
        $fkTable   = '_table_'.$attribute->getAttributeCode();

        $this->joinProducts()->getSelect()->joinLeft(
            array($fkTable => $attribute->getBackendTable()),
            $fkTable.'.entity_id = `cataloginventory/stock_item`.product_id AND '.
            $fkTable.'.attribute_id = '.$attribute->getAttributeId().' AND '.
            $fkTable.'.store_id = 0',
            array($alias => 'value')
        );

        return $this;
    }

    /**
     * Joins stock items to collection
     *
     * @return GlobalThinking_Inventory_Model_Mysql4_Stock_Transaction_Collection
     */
    public function joinStockItems()
    {
        $stockItemTable = array('cataloginventory/stock_item' => $this->getTable('cataloginventory/stock_item'));

        $fromPart = $this->getSelect()->getPart(Zend_Db_Select::FROM);
        if (! isset($fromPart['cataloginventory/stock_item'])) {
            $this->getSelect()->joinLeft(
                $stockItemTable,
                '`cataloginventory/stock_item`.item_id = main_table.item_id'
            );
        }
        return $this;
    }

    /**
     * Joins catalog products to collection
     *
     * @return GlobalThinking_Inventory_Model_Mysql4_Stock_Transaction_Collection
     */
    public function joinProducts()
    {
        $productTable   = array('catalog/product'  => $this->getTable('catalog/product'));

        $fromPart = $this->getSelect()->getPart(Zend_Db_Select::FROM);
        if (! isset($fromPart['catalog/product'])) {
            $this->joinStockItems()->getSelect()->joinLeft(
                $productTable,
                '`catalog/product`.entity_id = `cataloginventory/stock_item`.product_id',
                array('sku')
            );
        }
        return $this;
    }

    /**
     * Add website filter to collection
     *
     * @param Mage_Core_Model_Website|int|string|array $websites
     * @return GlobalThinking_Inventory_Model_Mysql4_Stock_Transaction_Collection
     */
    public function addWebsiteFilter($websites = null)
    {
        if (!is_array($websites)) {
            $websites = array(Mage::app()->getWebsite($websites)->getId());
        }

        $this->_productLimitationFilters['website_ids'] = $websites;
        $this->_productLimitationJoinWebsite();

        return $this;
    }

   /**
     * Join website product limitation
     *
     * @return GlobalThinking_Inventory_Model_Mysql4_Stock_Transaction_Collection
     */
    protected function _productLimitationJoinWebsite()
    {
        $joinWebsite = false;
        $filters     = $this->_productLimitationFilters;
        $conditions  = array(
            'product_website.product_id=`cataloginventory/stock_item`.product_id'
        );
        if (isset($filters['website_ids'])) {
            $joinWebsite = true;
            if (count($filters['website_ids']) > 1) {
                $this->getSelect()->distinct(true);
            }
            $conditions[] = $this->getConnection()
                ->quoteInto('product_website.website_id IN(?)', $filters['website_ids']);
        }

        $fromPart = $this->getSelect()->getPart(Zend_Db_Select::FROM);
        if (isset($fromPart['product_website'])) {
            if (!$joinWebsite) {
                unset($fromPart['product_website']);
            }
            else {
                $fromPart['product_website']['joinCondition'] = join(' AND ', $conditions);
            }
            $this->getSelect()->setPart(Zend_Db_Select::FROM, $fromPart);
        }
        elseif ($joinWebsite) {
            $this->joinProducts()->getSelect()->join(
                array('product_website' => $this->getTable('catalog/product_website')),
                join(' AND ', $conditions),
                array()
            );
        }

        return $this;
    }

}