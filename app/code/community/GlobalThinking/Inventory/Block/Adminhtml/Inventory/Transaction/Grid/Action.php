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
class GlobalThinking_Inventory_Block_Adminhtml_Inventory_Transaction_Grid_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $actions = array();
        if ( $row->hasParent() ) {
            // Check that the user has access to inventory receipts
            if ( $row->getParentType() == 'globalthinking_inventory/stock_receipt' && !$this->canViewReceipt() ) {
                $actions[] = array(
                    '@' => array('href' => '#', 'onclick' => 'alert(\'You do not have access to view this receipt.\')', 'style' => 'color: #ccc !important'),
                    '#' => $this->__('View %s', $this->getColumn()->getGrid()->getParentType($row))
                );
            // Check that the user has access to orders
            } else if ( in_array($row->getParentType(), array('sales/order','sales/quote')) && !$this->canViewOrder() ) {
                $actions[] = array(
                    '@' => array('href' => '#', 'onclick' => 'alert(\'You do not have access to view this order.\')', 'style' => 'color: #ccc !important'),
                    '#' => $this->__('View %s', $this->getColumn()->getGrid()->getParentType($row))
                );
            // Check that the user has access to credit memos
            } else if ( $row->getParentType() == 'sales/order_creditmemo' && !$this->canViewCreditmemo() ) {
                $actions[] = array(
                    '@' => array('href' => '#', 'onclick' => 'alert(\'You do not have access to view this credit memo.\')', 'style' => 'color: #ccc !important'),
                    '#' => $this->__('View %s', $this->getColumn()->getGrid()->getParentType($row))
                );
            // Default
            } else {
                $actions[] = array(
                    '@' =>  array('href' => trim($row->getParentUrl(),'/').'/popup/1/', 'target' => '_blank'),
                    '#' =>  $this->__('View %s', $this->getColumn()->getGrid()->getParentType($row) )
                );
            }
        }
        return $this->_actionsToHtml($actions);
    }

    protected function _getEscapedValue($value)
    {
        return addcslashes(htmlspecialchars($value),'\\\'');
    }

    protected function _actionsToHtml(array $actions)
    {
        $html = array();
        $attributesObject = new Varien_Object();

        foreach ($actions as $action) {
            $attributesObject->setData($action['@']);
            $html[] = '<a ' . $attributesObject->serialize() . '>' . $action['#'] . '</a>';
        }
        return  implode('<br />',$html);
    }

    public function canViewReceipt()
    {
        return Mage::getSingleton('admin/session')->isAllowed(GlobalThinking_Inventory_Model_Stock_Receipt::ACL_VIEW);
    }

    public function canViewOrder()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order') &&
            Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view');
    }

    public function canViewCreditmemo()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/creditmemo');
    }
}