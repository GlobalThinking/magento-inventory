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
class GlobalThinking_Inventory_Block_Adminhtml_Inventory_Grid_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $actions = array();
        $actions[] = array(
            '@' =>  array('href' => $this->getUrl('*/*/'.($this->isEditable() ? 'edit' : 'view'), array('item_id'=>$row->getItemId())) ),
            '#' =>  ($this->isEditable() ? $this->__('Edit') : $this->__('View'))
        );
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

    /**
     * Does the current user have access to edit the quantities?
     *
     * @return bool
     */
    public function isEditable()
    {
        return Mage::getSingleton('admin/session')->isAllowed(GlobalThinking_Inventory_Model_Stock_Item::ACL_UPDATE);
    }
}