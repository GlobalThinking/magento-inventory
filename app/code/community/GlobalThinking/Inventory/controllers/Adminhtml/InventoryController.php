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
class GlobalThinking_Inventory_Adminhtml_InventoryController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Load the layout and set the default title
     */
    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('catalog')
            ->_addBreadcrumb($this->__('Catalog'), $this->__('Catalog'))
            ->_title($this->__('Catalog'))
            ->_title($this->__('Manage Inventory'));

        return $this;
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        $action = strtolower($this->getRequest()->getActionName());
        switch ($action) {
            case 'index':
            case 'grid':
                $aclResource = GlobalThinking_Inventory_Model_Stock_Item::ACL_VIEW;
                break;
            case 'save':
            case 'massUpdate':
                $aclResource = GlobalThinking_Inventory_Model_Stock_Item::ACL_UPDATE;
                break;
            default:
                $aclResource = GlobalThinking_Inventory_Model_Stock_Item::ACL_MENU.'/actions';
                break;
        }
        return Mage::getSingleton('admin/session')->isAllowed($aclResource);
    }

    /**
     * Load and render the layout for the grid of inventory items
     */
    public function indexAction()
    {
        $this->_initAction();
        $this->_title('Items');
        $this->renderLayout();
    }

    public function viewAction()
    {
        $this->_forward('edit');
    }

    /**
     * Load the layout and figure out which product/item we are going to edit
     */
    public function editAction()
    {
        $this->_initAction();

        // Initialize the item
        $itemId      = $this->getRequest()->getParam('item_id');
        $item        = Mage::getModel('cataloginventory/stock_item')->load($itemId);

        // Initialize the product
        $productId   = $item->getProductId();
        $product     = Mage::getModel('catalog/product')->load($productId);

        // Store the item and product in the registry
        Mage::register('current_item',    $item);
        Mage::register('current_product', $product);

        // Render the layout
        $this->renderLayout();
    }

    /**
     * Handle all save actions when we are editing or creating an inventory item
     */
    public function saveAction()
    {
        // Parse url parameters
        $redirectBack  = $this->getRequest()->getParam('back', false);
        $itemId        = $this->getRequest()->getParam('item_id');
        $isEdit        = (int)($this->getRequest()->getParam('item_id') != null);

        // Process the POST data
        if ( $postData = $this->getRequest()->getPost() ) {
            // Initialize the item/product models
            $item    = Mage::getModel('cataloginventory/stock_item')->load($itemId);
            $product = Mage::getModel('catalog/product')->load($item->getProductId());

            if ( $item->getId() && $product->getId() ) {
                // Save the new data to the item
                $item->addData($postData)->save();

                // Set the successful message
                if ( $redirectBack ) {
                    $message = $this->__('The item has been saved!');
                } else {
                    $message = $this->__('%s (%s) has been saved',$product->getName(),$product->getSku());
                }
                Mage::getSingleton('adminhtml/session')->addSuccess($message);
            } else {
                // An error occurred, so set the error message
                $message = $this->__('The item no longer exists.');
                Mage::getSingleton('adminhtml/session')->addError($message);
            }
        }

        // Handle redirection for AJAX saves
        if ($redirectBack) {
            $this->_redirect('*/*/edit', array(
                'item_id'    => $itemId,
                'tab'        => $this->getRequest()->getParam('tab')
            ));
        // TODO Handle redirection for popup saves
        } else if($this->getRequest()->getParam('popup')) {
            $this->_redirect('*/*/created', array(
                '_current'   => true,
                'edit'       => $isEdit
            ));
        // Handle redirection for typical saves
        } else {
            $this->_redirect('*/*/');
        }
    }

    /**
     * Save handler when multiple inventory items are updated
     */
    public function massUpdateAction()
    {
        $postData = $this->getRequest()->getPost();

        // Error handling
        if ( empty( $postData['inventory']) ) {
            $message = $this->__('Error: No items selected');
            Mage::getSingleton('adminhtml/session')->addError($message);

            $this->_redirect('*/*');
            return;
        }

        // Decode the serialized data
        $inventory = Mage::helper('adminhtml/js')->decodeGridSerializedInput( $postData['inventory'] );

        // Iterate through all of the POST data and save the new inventory data
        foreach ( $inventory as $productId => $data ) {
            $product = Mage::getModel('catalog/product')->load($productId);
            $item = Mage::getModel('cataloginventory/stock_item')
                ->loadByProduct($productId)
                ->setQtyOnHand($data['qty_on_hand'])
                ->save();

            // Set a successful message for each item
            $message = $this->__('%s (%s) has been saved. Qty on Hand: %s',$product->getName(),$product->getSku(),$data['qty_on_hand']);
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        }

        // Redirect back to the inventory list
        $this->_redirect('*/*');
    }

    /**
     * Ajax action for the inventory grid
     */
    public function gridAction()
    {
        // We have to initialize the action so that the formkey is preserved for the massaction
        $this->_initAction()->getResponse()->setBody(
            $this->getLayout()->createBlock('globalthinking_inventory/adminhtml_inventory_grid')->toHtml()
        );
    }
}