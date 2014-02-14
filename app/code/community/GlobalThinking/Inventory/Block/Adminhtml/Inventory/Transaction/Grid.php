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
class GlobalThinking_Inventory_Block_Adminhtml_Inventory_Transaction_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId('globalthinking_inventory_transaction_grid');

        $this->setUseAjax(true);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _getCollectionClass()
    {
        return 'globalthinking_inventory/stock_transaction_collection';
    }

    protected function _prepareCollection()
    {
        /* @var $collection GlobalThinking_Inventory_Model_Mysql4_Stock_Transaction_Collection */
        $collection = Mage::getResourceModel($this->_getCollectionClass())
            ->addExpressionFieldToSelect('adjustment_positive','(IF(adjustment > 0,abs({{adjustment}}),0))','adjustment')
            ->addExpressionFieldToSelect('adjustment_negative','(IF(adjustment < 0,abs({{adjustment}}),0))','adjustment');

        // Get the product name attribute to join
        $collection->addAttributeToSelect('name','product_name');

        if ( $item = Mage::registry('current_item') ) {
            $collection->addFieldToFilter('main_table.item_id',$item->getId());
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn( 'id', array(
            'filter_index' => 'main_table.entity_id',
            'header'   => $this->__('Txn ID'),
            'index'    => 'entity_id',
            'width'    => '70px'
        ));

        $this->addColumn( 'created_at', array(
            'filter_index' => 'main_table.created_at',
            'header'   => $this->__('Timestamp'),
            'index'    => 'created_at',
            'type'     => 'datetime',
            'width'    => '180px'
        ));

        $this->addColumn( 'product_name', array(
            'header'   => $this->__('Product Name'),
            'index'    => 'product_name',
            // table name derived from GlobalThinking_Inventory_Model_Mysql4_Stock_Transaction_Collection::addAttributeToSelect()
            'filter_index' => '_table_name.value'
        ));

        $this->addColumn( 'product_sku', array(
            'header'   => $this->__('Sku'),
            'index'    => 'sku',
            'width'    => '120px'
        ));

        $this->addColumn( 'parent_type', array(
            'header'   => $this->__('Type'),
            'getter'   => array($this,'getParentType'),
            'index'    => 'parent_type',
            'filter'   => 'adminhtml/widget_grid_column_filter_select',
            'options'  => $this->getParentTypeOptions(),
            'width'    => '100px'
        ));

        $this->addColumn( 'adjustment_positive', array(
            'filter_index' => 'adjustment',
            'header'   => $this->__('Adjustment (+)'),
            'index'    => 'adjustment_positive',
            'type'     => 'number',
            'width'    => '120px'
        ));

        $this->addColumn( 'adjustment_negative', array(
            'filter_index' => 'adjustment',
            'header'   => $this->__('Adjustment (-)'),
            'index'    => 'adjustment_negative',
            'type'     => 'number',
            'width'    => '120px'
        ));

        $this->addColumn( 'balance', array(
            'header'   => $this->__('Balance'),
            'index'    => 'balance',
            'type'     => 'number',
            'width'    => '120px'
        ));

        $this->addColumn('action', array(
            'header'  => ' ',
            'filter'  => false,
            'renderer'=> 'globalthinking_inventory/adminhtml_inventory_transaction_grid_action',
            'width'   => '120px',
        ));
    }

    protected function _setCollectionOrder($column)
    {
        switch ( $column->getId() ) {
            // Reverse sort adjustment_negative because we are taking the absolute value
            case 'adjustment_negative':
                $collection = $this->getCollection();
                if ($collection) {
                    $direction = $column->getDir();

                    // Flip the direction
                    $direction = (strtoupper($direction) == Varien_Data_Collection_Db::SORT_ORDER_ASC) ?
                        Varien_Data_Collection_Db::SORT_ORDER_DESC : Varien_Data_Collection_Db::SORT_ORDER_ASC;

                    $columnIndex = $column->getFilterIndex() ?
                        $column->getFilterIndex() : $column->getIndex();
                    $collection->setOrder($columnIndex, $direction);
                }
                return $this;
                break;

            // Default behavior
            default:
                return parent::_setCollectionOrder($column);
                break;
        }
    }

    protected function _addColumnFilterToCollection($column)
    {

        $field = ( $column->getFilterIndex() ) ? $column->getFilterIndex() : $column->getIndex();
        $cond  = $column->getFilter()->getCondition();

        // Make sure adjustment_positive is not including negative
        if ( $column->getId() == 'adjustment_positive' ) {
            $cond['from'] = isset($cond['from']) ? $cond['from'] : 0;

            $this->getCollection()->addFieldToFilter($field,$cond);

        // Swap 'from' and 'to' for adjustment_negative, make sure to not include positives
        } else if ( $column->getId() == 'adjustment_negative' ) {
            if ( isset($cond['from']) && isset($cond['to']) ) {
                $from  = 0 - $cond['from'];
                $to    = 0 - $cond['to'];
                $cond['from'] = min($from, $to);
                $cond['to']   = max($from, $to);
            } else if ( isset($cond['from']) ) {
                $from         = 0 - $cond['from'];
                $cond['to']   = $from;
                unset($cond['from']);
            } else {
                $to           = 0 - $cond['to'];
                $cond['from'] = $to;
                $cond['to']   = 0;
            }

            $this->getCollection()->addFieldToFilter($field,$cond);

        // Filtering parent types is tricky
        } else if ( $column->getId() == 'parent_type' ) {
            // Fix manual transaction filter
            if ( $cond['eq'] == 'manual' ) {
                $cond['eq'] = '';

                $this->getCollection()->addFieldToFilter($field,$cond);

            // Include canceled and new orders
            } else if ( $cond['eq'] == 'order' ) {
                $cond = array('in',array('sales/quote','sales/order'));

                $this->getCollection()->addFieldToFilter($field,$cond);

            // Default behavior
            } else {
                parent::_addColumnFilterToCollection($column);
            }

        // Default behavior
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    public function getParentType( $row )
    {
        $options = $this->getParentTypeOptions();

        switch ( $row->getParentType() ) {
            case 'sales/order':
            case 'sales/quote':
                return $options['order'];
                break;

            default:
                return isset($options[$row->getParentType()]) ? $options[$row->getParentType()] : '';
                break;
        }
    }

    public function getParentTypeOptions()
    {
        return array(
            'manual'                 => $this->__('Manual'),
            'order'                  => $this->__('Order'),
            'sales/order_creditmemo' => $this->__('Credit Memo'),
            'globalthinking_inventory/stock_receipt' => $this->__('Receipt')
        );
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/inventory_transaction/grid',array('_current'=>true));
    }

    public function getAbsoluteGridUrl($params = array())
    {
        return $this->getUrl('*/inventory_transaction/grid',$params);
    }

}