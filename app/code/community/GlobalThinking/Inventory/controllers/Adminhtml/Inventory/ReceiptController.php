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
class GlobalThinking_Inventory_Adminhtml_Inventory_ReceiptController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Load the layout and set the default title
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('catalog')
            ->_addBreadcrumb($this->__('Catalog'), $this->__('Catalog'))
            ->_title($this->__('Catalog'))
            ->_title($this->__('Manage Inventory'))
            ->_title($this->__('Receipts'));

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
            case 'new':
                $aclResource = GlobalThinking_Inventory_Model_Stock_Receipt::ACL_CREATE;
                break;
            case 'view':
                $aclResource = GlobalThinking_Inventory_Model_Stock_Receipt::ACL_VIEW;
                break;
            default:
                $aclResource = GlobalThinking_Inventory_Model_Stock_Receipt::ACL_MENU.'/actions';
                break;
        }
        return Mage::getSingleton('admin/session')->isAllowed($aclResource);
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function viewAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $this->_initAction();

        // Initialize the item
        $receiptId = $this->getRequest()->getParam('receipt_id');
        $receipt   = Mage::getModel('globalthinking_inventory/stock_receipt')->load($receiptId);

        $this->_title($this->__('Receipt # %s', $receipt->getIncrementId()));

        // Store the item and product in the registry
        Mage::register('current_receipt', $receipt);

        $this->renderLayout();
    }

    /**
     * Saves the receipt when creating or editing
     */
    public function saveAction()
    {
        // Parse url parameters
        $redirectBack  = $this->getRequest()->getParam('back', false);
        $receiptId     = $this->getRequest()->getParam('receipt_id');
        $isEdit        = (int)($this->getRequest()->getParam('receipt_id') != null);

        // Process the POST data
        if ( $postData = $this->getRequest()->getPost() ) {
            if ( !$isEdit && ( empty($postData['products']) ||
                    ! ($products = Mage::helper('adminhtml/js')->decodeGridSerializedInput($postData['products'])))) {
                $redirectBack = true;
                $message = $this->__('You must select at least one product');
                Mage::getSingleton('adminhtml/session')->addError($message);
                Mage::getSingleton('adminhtml/session')->setReceiptData($postData);
            } else {
                $reference_id = $postData['reference_id'];
                $order = Mage::getModel('sales/order')->loadByIncrementId($reference_id);

                if($postData['reference_type'] == 'returned_item' && !$order->getId()){
                    $redirectBack = true;

                    $message = $this->__('Each "Returned Item" must have a valid order number in the "Reference Number" field. No order found for the order number entered');
                    Mage::getSingleton('adminhtml/session')->addError($message);
                    Mage::getSingleton('adminhtml/session')->setReceiptData($postData);
                } else {

                    // Check that there is at least something with a quantity
                    if ( $isEdit ) {
                        $proceed = true;
                    } else {
                        $proceed = false;
                        foreach ( $products as $product ) {
                            if ( $product['qty_received'] != 0 ) {
                                $proceed = true;
                            }
                        }
                    }

                    if ( $proceed ) {
                        $receipt = Mage::getModel('globalthinking_inventory/stock_receipt');
                        if ( $isEdit ) $receipt->load($receiptId);

                        if ( !empty($postData['shipment']) ) {
                            $receipt->addExtraInfo($postData['shipment']);
                        }

                        if ( !empty($postData['production']) ) {
                            $receipt->addExtraInfo($postData['production']);
                        }

                        //if returned_item, save the order_number for the increment_id entered
                        if ( $postData['reference_type'] == 'returned_item' ) {
                            $postData['reference_id'] = $order->getId();
                        }

                        $receipt->addData($postData);
                        $receipt->save();

                        // Only process products for a new receipt
                        if ( !$isEdit ) {
                            foreach ( $products as $productId => $info ) {
                                if ( $info['qty_received'] == 0 ) continue;
                                $item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
                                if ( $item->getId() ) {
                                    Mage::helper('globalthinking_inventory')->getStockTransaction()
                                        ->setParentType('globalthinking_inventory/stock_receipt')
                                        ->setParentId($receipt->getId());

                                    $item->setQty($item->getQty() + $info['qty_received'])->save();
                                }
                            }

                            // Update the receipt id (for redirects)
                            $receiptId = $receipt->getId();
                        }

                        $message = $this->__('The receipt has been saved');
                        Mage::getSingleton('adminhtml/session')->addSuccess($message);
                    } else {
                        $redirectBack = true;
                        $message = $this->__('You must select at least one product');
                        Mage::getSingleton('adminhtml/session')->addError($message);
                        Mage::getSingleton('adminhtml/session')->setReceiptData($postData);
                    }
                }
            }
        }

        // Handle redirection for AJAX saves and errors
        if ($redirectBack) {
            $this->_redirect('*/*/'.($isEdit ? 'edit' : 'new'), array(
                'receipt_id' => $receiptId,
                'tab'        => $this->getRequest()->getParam('tab'),
                'popup'      => $this->getRequest()->getParam('popup')
            ));
        // TODO Handle redirection for popup saves
        } else if($this->getRequest()->getParam('popup')) {
            $this->_redirect('*/*/created', array(
                '_current'   => true,
                'edit'       => $isEdit
            ));
        // Handle redirection for typical saves
        } else {
            $this->_redirect('*/inventory_transaction');
        }
    }

    /**
     * Chooser Source action
     */
    public function chooserAction()
    {
        $uniqId        = $this->getRequest()->getParam('uniq_id');
        $massAction    = $this->getRequest()->getParam('use_massaction', false);
        $productTypeId = $this->getRequest()->getParam('product_type_id', null);
        $readonly      = $this->getRequest()->getParam('readonly', false);
        $receiptId     = $this->getRequest()->getParam('receipt_id');

        /* @var $productsGrid GlobalThinking_Inventory_Block_Adminhtml_Inventory_Receipt_Create_Search */
        $productsGrid = $this->getLayout()->createBlock('globalthinking_inventory/adminhtml_inventory_receipt_create_search', 'chooser', array(
            'id'                => $uniqId,
            'use_massaction'    => !$readonly && $massAction,
            'product_type_id'   => $productTypeId,
            'category_id'       => $this->getRequest()->getParam('category_id'),
            'readonly'          => $readonly,
            'receipt_id'        => $receiptId
        ));

        $html = $productsGrid->toHtml();

        if (!($readonly && $receiptId) && !$this->getRequest()->getParam('products_grid')) {
            $categoriesTree = $this->getLayout()->createBlock('adminhtml/catalog_category_widget_chooser', '', array(
                'id'                  => $uniqId.'Tree',
                'node_click_listener' => $productsGrid->getCategoryClickListenerJs(),
                'with_empty_node'     => true
            ));

            $html = $this->getLayout()->createBlock('adminhtml/catalog_product_widget_chooser_container')
                ->setTreeHtml($categoriesTree->toHtml())
                ->setGridHtml($html)
                ->toHtml();

            /* @var $serializer Mage_Adminhtml_Block_Widget_Grid_Serializer */
            $serializer = $this->getLayout()->createBlock('adminhtml/widget_grid_serializer','serializer');

            $serializer->initSerializerBlock(
                $productsGrid,
                'getSelectedProducts',
                'products',
                'selected_products'
            );

            $serializer->addColumnInputName('qty_received');

            $html.= $serializer->toHtml();
        }

        $this->getResponse()->setBody($html);
    }
    /**
     * action to display create/edit successful page
     */
    public function createdAction()
    {
        $this->_getSession()->addNotice(
            Mage::helper('globalthinking_inventory')->__('Please click on the Close Window button if it is not closed automatically.')
        );
        $this->loadLayout('popup');
        $this->_addContent(
            $this->getLayout()->createBlock('globalthinking_inventory/adminhtml_inventory_receipt_create_created')
        );
        $this->renderLayout();
    }

    /*
     * Action to display a popup to choose a particular order
     */
    public function selectOrderAction()
    {

        $this->loadLayout('popup');
        $this->renderLayout();
    }
}