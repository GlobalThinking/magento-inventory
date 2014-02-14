<?php
/**
 * Global Thinking
 *
 * @category    Magento
 * @package     GlobalThinking_Inventory
 * @copyright   Copyright (c) 2011 Global Thinking, Inc. (http://www.globalthinking.com)
 */

/**
 * @category   Magento
 * @package    GlobalThinking_Inventory
 * @author     Franklin Strube <fstrube@globalthinking.com>
 */
class GlobalThinking_Inventory_Model_Stock_Receipt extends Mage_Core_Model_Abstract
{
    const ACL_MENU   = 'catalog/globalthinking_inventory/receipts';
    const ACL_VIEW   = 'catalog/globalthinking_inventory/receipts/actions/view';
    const ACL_CREATE = 'catalog/globalthinking_inventory/receipts/actions/create';
    const ACL_UPDATE = 'catalog/globalthinking_inventory/receipts/actions/update';

    protected $_extraInfo;

    protected function _construct()
    {
        parent::_construct();
        $this->_init('globalthinking_inventory/stock_receipt');
    }

    public function getExtraInfo($key = '', $index = null )
    {
        if ( !$this->_extraInfo ) {
            $extra = $this->getExtra();
            $extra = empty($extra) ? array() : unserialize($extra);
            $this->_extraInfo = new Varien_Object($extra);
        }

        return $this->_extraInfo->getData($key, $index);
    }

    public function addExtraInfo(array $extra)
    {
        $this->getExtraInfo();

        $this->_extraInfo->addData($extra);
    }

    public function getItemCollection()
    {
        $items = Mage::getResourceModel('globalthinking_inventory/stock_transaction_collection')
            ->addFieldToFilter('parent_type', $this->getResourceName())
            ->addFieldToFilter('parent_id', $this->getId());

        return $items;
    }

    protected function _beforeSave()
    {
        if ( !$this->getIncrementId() ) {
            /* @var $config Mage_Eav_Model_Config */
            /* @var $entityType Mage_Eav_Model_Entity_Type */
            /* @var $increment Mage_Eav_Model_Entity_Increment_Numeric */
            /* @var $entityStoreConfig Mage_Eav_Model_Entity_Store */
            $incrementId = Mage::getSingleton('eav/config')
                ->getEntityType('stock_receipt')
                ->fetchNewIncrementId();

            $this->setIncrementId($incrementId);
        }

        if ( $this->_extraInfo ) {
            Mage::log($this->getExtraInfo('cost'));
            $this->setExtra(serialize($this->getExtraInfo()));
        }

        return parent::_beforeSave();
    }
}