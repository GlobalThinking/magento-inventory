<?php
/**
 * Global Thinking
 *
 * @category    Magento
 * @package     GlobalThinking_Inventory
 * @copyright   Copyright (c) 2010 Global Thinking, Inc. (http://www.globalthinking.com)
 */

/**
 * Product after creation popup window
 *
 * @category   Magento
 * @package    GlobalThinking_Inventory
 * @author     Franklin Strube <fstrube@globalthinking.com>
 */
class GlobalThinking_Inventory_Block_Adminhtml_Inventory_Receipt_Create_Created extends Mage_Adminhtml_Block_Widget
{
    protected $_configurableProduct;
    protected $_product;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('globalthinking/inventory/create/created.phtml');
    }


    protected function _prepareLayout()
    {
        $this->setChild(
            'close_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'   => Mage::helper('adminhtml')->__('Close Window'),
                    'onclick' => 'closeWindow()'
                ))
        );
    }

    /*
    protected function _toHtml()
    {
        return $this->getCloseButtonHtml();
    }
    */

    public function getCloseButtonHtml()
    {
        return $this->getChildHtml('close_button');
    }
}
