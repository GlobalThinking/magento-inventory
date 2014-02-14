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
class GlobalThinking_Inventory_Block_Adminhtml_Inventory_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructor sets the defaults for this grid
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('globalthinking_inventory_grid');

        $this->setUseAjax(true);
        $this->setDefaultSort('name');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Setup the data collection for this grid
     *
     * @return GlobalThinking_Inventory_Block_Adminhtml_Inventory_Grid
     */
    protected function _prepareCollection()
    {
        $alias = array(
            'qty_on_sale' => '`cataloginventory/stock_item`.qty',
        );

        $stockFields = array(
            'qty_on_sale' => 'qty',
            'item_id'     => 'item_id'
        );

        /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');

        $collection
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('short_description')
            ->joinTable('cataloginventory/stock_item','product_id=entity_id',$stockFields,null,'left');

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Setup the columns to display for this grid
     *
     * @return GlobalThinking_Inventory_Block_Adminhtml_Inventory_Grid
     */
    protected function _prepareColumns()
    {

        $this->addColumn( 'product_sku', array(
            'header'   => $this->__('Sku'),
            'index'    => 'sku',
            'width'    => '120px'
        ));

        $this->addColumn( 'product_name', array(
            'header'   => $this->__('Product Name'),
            'index'    => 'name'
        ));

        $this->addColumn( 'qty_on_sale', array(
            'header'   => $this->__('Qty on Sale'),
            'index'    => 'qty_on_sale',
            'type'     => 'number',
            'width'    => '120px'
        ));

        $this->addColumn( 'qty_to_ship', array(
            'filter_condition_callback' => array($this,'_filterQtyToShip'),
            'getter'   => array($this,'getQtyToShip'),
            'header'   => $this->__('Qty to Ship'),
            'index'    => 'qty_to_ship',
            'type'     => 'number',
            'width'    => '120px',
            'sortable' => false
        ));

        $this->addColumn( 'qty_on_hand', array(
            'filter_condition_callback' => array($this,'_filterQtyOnHand'),
            'getter'   => array($this,'getQtyOnHand'),
            'header'   => $this->__('Qty on Hand'),
            'index'    => 'qty_on_hand',
            'type'     => 'number',
            'width'    => '120px',
            'editable' => $this->isEditable(),
            'sortable' => false
        ));

        $this->addColumn('action', array(
            'column_css_class' => 'a-center',
            'header'  => $this->__('Action'),
            'filter'  => false,
            'renderer'=> 'globalthinking_inventory/adminhtml_inventory_grid_action',
            'sortable'=> false,
            'width'   => '50px',
        ));

        return parent::_prepareColumns();
    }

    protected function _filterQtyToShip($collection, $column)
    {
        $data = $column->getFilter()->getValue();

        Mage::helper('globalthinking_inventory')->filterQtyToShip($collection, $data);
    }

    protected function _filterQtyOnHand($collection, $column)
    {
        $data = $column->getFilter()->getValue();

        Mage::helper('globalthinking_inventory')->filterQtyOnHand($collection, $data);
    }

    /**
     * Setup the massaction header
     */
    protected function _prepareMassaction()
    {
        /*
         * $item = array(
         *      'label'    => string,
         *      'complete' => string, // Only for ajax enabled grid (optional)
         *      'url'      => string,
         *      'confirm'  => string, // text of confirmation of this action (optional)
         *      'additional' => string|array|Mage_Core_Block_Abstract // (optional)
         * );
         */
        if ( $this->isEditable() ) {
            $this->getMassactionBlock()->addItem('update',array(
                'label'    => $this->__('Update Qty on Hand'),
                'complete' => "function(grid,massaction,transport){
                        massaction.unselectAll();
                        grid.reload();
                    }
                }",
                'url'      => $this->getUrl('*/inventory/massUpdate'),
                'confirm'  => $this->__('Are you sure you want to update the selected quantities?')
            ));

            $this->getMassactionBlock()
                //->setUseAjax(true)
                ->setUseSelectAll(false);

            $this->setMassactionIdField('entity_id');

            // Synchronize the hidden input in the massaction form!
            if ( ($parent = $this->getParentBlock()) && ($serializer = $parent->getChild( 'serializer' )) ) {
                $serializer->setFormId( $this->getMassactionBlock()->getHtmlId().'-form' );
            }
        }
    }

    /**
     * Add a .checkbox class to the massaction checkbox
     */
    protected function _prepareMassactionColumn()
    {
        parent::_prepareMassactionColumn();
        $this->addColumn('massaction',array(
            'align'      => 'center',
            'field_name' => $this->getMassactionBlock()->getFormFieldName(),
            'header'     => ' ',
            'index'      => $this->getMassactionIdField(),
            'inline_css' => 'massaction-checkbox checkbox',
            'type'       => 'checkbox'
        ));
        return $this;
    }

    /**
     * Does the current user have access to edit the quantities?
     *
     * @return bool
     */
    public function isEditable()
    {
        return Mage::getSingleton('admin/session')->isAllowed(GlobalThinking_Inventory_Model_Stock_Item::ACL_UPDATE);
    }

    /**
     * The row class (pointer if editable)
     *
     * @param $row Mage_Catalog_Model_Product
     */
    public function getRowClass( $row )
    {
        $class = array();

        if ( $this->isEditable() ) $class['pointer'] = true;

        /* @var $item GlobalThinking_Inventory_Model_Stock_Item */
        $item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($row->getId());
        if ( $item->getQty() <= $item->getMinQty() ) {
            $class['invalid'] = true;
        } elseif ( $item->getQty() <= $item->getNotifyStockQty() ) {
            $class['emph']    = true;
        }

        return implode(' ',array_keys($class));
    }

    /**
     * The row url (empty)
     *
     * @param $row Mage_Catalog_Model_Product
     * @return string
     */
    public function getRowUrl( $row )
    {
        return ''; //$row->getShortDescription() ? $row->getShortDescription() : 'No Description';
    }

    /**
     * Disable row click (only works without massaction or serializer)
     */
    public function getRowClickCallback()
    {
        return 'function(){}';
    }

    /**
     * Setup mouseover for rows without a url
     */
    public function getRowInitCallback()
    {
        return "function( grid, row ) {
            Element.observe(row,'mouseover',function(event) {
                Element.addClassName(this,'on-mouse');
            }.bindAsEventListener(row));
        }";
    }

    /**
     * Addition JavaScript for the grid
     *   - override how massaction handles checkbox and
     *   - fix serializer from double-checking checkbox
     */
    public function getAdditionalJavascript()
    {
        return "

        {$this->getMassactionBlock()->getJsObjectName()}.checkCheckboxes = function() {
            this.getCheckboxes().each(function(checkbox) {
                var checked = varienStringArray.has(checkbox.value, this.checkedString);
                this.grid.setCheckboxChecked(checkbox, checked);
            }.bind(this));
        }.bind({$this->getMassactionBlock()->getJsObjectName()});

        {$this->getMassactionBlock()->getJsObjectName()}.setCheckbox = function(checkbox) {
            this.grid.setCheckboxChecked(checkbox,checkbox.checked);
            if(checkbox.checked) {
                this.checkedString = varienStringArray.add(checkbox.value, this.checkedString);
            } else {
                this.checkedString = varienStringArray.remove(checkbox.value, this.checkedString);
            }
            this.updateCount();
        }.bind({$this->getMassactionBlock()->getJsObjectName()});

        ";
    }

    /**
     * Ajax grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/inventory/grid');
    }

    /**
     * Ajax grid url
     *
     * @param array $params the url parameters
     * @return string
     */
    public function getAbsoluteGridUrl($params = array())
    {
        return $this->getUrl('*/inventory/grid',$params);
    }

    /**
     * Calculate the quantity to ship
     *
     * @param $row Mage_Catalog_Model_Product
     * @return int
     */
    public function getQtyToShip( $row )
    {
        if ( $row->getQtyToShip() === null ) {
            $item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($row->getId());

            $row->setQtyToShip($item->getQtyToShip());
            //$row->setQtyToShip($qtyToShip ? $qtyToShip : null);
        }

        return $row->getQtyToShip();
    }

    /**
     * Calculate the quantity on hand for a particular row (based on qty on sale + qty to ship)
     *
     * @param $row Mage_Catalog_Model_Product
     * @return int
     */
    public function getQtyOnHand( $row )
    {

        if ( $row->getQtyOnHand() === null ) {
            $qtyOnHand = $row->getQtyToShip() + $row->getQtyOnSale();

            $row->setQtyOnHand($qtyOnHand);
        }
        return $row->getQtyOnHand();
    }

}