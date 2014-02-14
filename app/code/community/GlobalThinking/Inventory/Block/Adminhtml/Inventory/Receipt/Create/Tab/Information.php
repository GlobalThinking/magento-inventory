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
class GlobalThinking_Inventory_Block_Adminhtml_Inventory_Receipt_Create_Tab_Information extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        // Prep the fieldsets
        $generalFieldset = $form->addFieldset('general_info', array(
            'legend'  => $this->__('General Information')
        ));

        $extraFieldset = $form->addFieldset('extra_info', array(
            'legend'  => $this->__('Extra Information')
        ));

        // Prep the form data
        $data = array();
        if ( $receipt = Mage::registry('current_receipt') ) {
            $data+= $receipt->getData();
            if ( !empty($data) && $data['reference_type'] == 'shipment' ) {
                $data['shipment_box_count']  = $receipt->getExtraInfo('box_count');
                $data['shipment_street1']    = $receipt->getExtraInfo('street',0);
                $data['shipment_street2']    = $receipt->getExtraInfo('street',1);
                $data['shipment_city']       = $receipt->getExtraInfo('city');
                $data['shipment_country_id'] = $receipt->getExtraInfo('country_id');
                $data['shipment_region_id']  = $receipt->getExtraInfo('region_id');
                $data['shipment_postcode']   = $receipt->getExtraInfo('postcode');
            }
            if ( !empty($data) && $data['reference_type'] == 'production' ) {
                $data['production_cost']     = $receipt->getExtraInfo('cost');
            }
            // for returned_item s display the increment id for the particular order
            if ( !empty($data) && $data['reference_type'] == 'returned_item' && !empty($data['reference_id'])) {
                $order = Mage::getModel('sales/order')->load($data['reference_id']);
                if($order->getId()){
                    $data['reference_id'] = $order->getIncrementId();
                }
            }
        }

        if ( Mage::getSingleton('adminhtml/session')->getReceiptData() ) {
            $data = array_merge($data, Mage::getSingleton('adminhtml/session')->getReceiptData());

            // Reformat shipment info
            if ( !empty($data['shipment']) ) {
                $data['shipment_box_count']  = $data['shipment']['box_count'];
                $data['shipment_street1']    = $data['shipment']['street'][0];
                $data['shipment_street2']    = $data['shipment']['street'][1];
                $data['shipment_city']       = $data['shipment']['city'];
                $data['shipment_country_id'] = $data['shipment']['country_id'];
                $data['shipment_region_id']  = $data['shipment']['region_id'];
                $data['shipment_postcode']   = $data['shipment']['postcode'];
                unset($data['shipment']);
            }

            // Reformat production info
            if ( !empty($data['production']) ) {
                $data['production_cost']     = $data['production']['cost'];
                unset($data['production']);
            }

            Mage::getSingleton('adminhtml/session')->setReceiptData(null);
        }

        // Add fields to the receipt fieldset
        $generalFieldset->addField('reference_type', 'select', array(
            'label'     => $this->__('Type'),
            'name'      => 'reference_type',
            'options'   => $this->getReferenceTypes(),
            'disabled'  => !$this->canEdit(),
            'note'   => $this->__('Do not use <em>%s</em> when you have already issued a credit memo for that order! (i.e. - CASE conferences)', $this->__('Loaned Item Return'))
        ));

        $generalFieldset->addField('reference_id', ($this->canEdit() ? 'text' : 'label'), array(
                'label'     => $this->__('Reference Number'),
                'name'      => 'reference_id',
                // lookup order button
                'after_element_html'=> '<button id="lookupOrderButton" type="button" style="margin:5px 0;" class="form-button" onclick="window.open(\''
                                        .Mage::helper("adminhtml")->getUrl('*/inventory_receipt/selectOrder',array())
                                        .'\',\'\',\'\');"><span>'
                                        .$this->__('Lookup Order')
                                        .'</span></button>'
            ));

        /*$generalFieldset->addField('lookup_button', 'button' , array(
            'name'      => 'lookup_button',
            'value'     => $this->__('### Number')
        )); */

        $generalFieldset->addField('name', ($this->canEdit() ? 'text' : 'label'), array(
            'label'     => $this->__('Name'),
            'name'      => 'name'
        ));

        $generalFieldset->addField('comment', ($this->canEdit() ? 'textarea' : 'label'), array(
            'label'     => $this->__('Comment'),
            'name'      => 'comment'
        ));

        // Add fields for production
        if ( $this->canEdit() || $data['reference_type'] == 'production' ) {
            $extraFieldset->addField('production_cost', ($this->canEdit() ? 'text' : 'label'), array(
                'label'     => $this->__('Production Cost'),
                'name'      => 'production[cost]'
            ));
        }

        // Add fields for shipment
        if ( $this->canEdit() || $data['reference_type'] == 'shipment' ) {
            $extraFieldset->addField('shipment_box_count', ($this->canEdit() ? 'text' : 'label'), array(
                'label'     => $this->__('No. of Boxes'),
                'name'      => 'shipment[box_count]'
            ));

            $extraFieldset->addField('shipment_street1', ($this->canEdit() ? 'text' : 'label'), array(
                'label'     => $this->__('Street'),
                'name'      => 'shipment[street][0]'
            ));

            $extraFieldset->addField('shipment_street2', ($this->canEdit() ? 'text' : 'label'), array(
                'label'     => ' ',
                'name'      => 'shipment[street][1]'
            ));

            $extraFieldset->addField('shipment_city', ($this->canEdit() ? 'text' : 'label'), array(
                'label'     => $this->__('City'),
                'name'      => 'shipment[city]'
            ));

            if ( $this->canEdit() ) {
                $extraFieldset->addField('shipment_country_id', 'select', array(
                    'label'     => $this->__('Country'),
                    'class'     => 'countries',
                    'name'      => 'shipment[country_id]',
                    'values'    => Mage::getModel('adminhtml/system_config_source_country')->toOptionArray()
                ));

                $extraFieldset->addField('shipment_region_id', ($this->canEdit() ? 'text' : 'label'), array(
                    'label'     => $this->__('State/Province'),
                    'name'      => 'shipment[region_id]'
                ));
            } else {
                /* @var $country Mage_Directory_Model_Country */
                $country = Mage::getModel('directory/country')->load($data['shipment_country_id']);
                $data['shipment_country_name'] = $country->getName();

                /* @var $region Mage_Directory_Model_Region */
                if ( is_numeric($data['shipment_region_id']) ) {
                    $region = Mage::getModel('directory/region')->load($data['shipment_region_id']);
                } else {
                    $region = new Varien_Object(array(
                        'name' => $data['shipment_region_id']
                    ));
                }
                $data['shipment_region_name'] = $region->getName();

                $extraFieldset->addField('shipment_country_name', 'label', array(
                    'label'     => $this->__('Country')
                ));

                $extraFieldset->addField('shipment_region_name', 'label', array(
                    'label'     => $this->__('State/Province'),
                ));
            }

            $extraFieldset->addField('shipment_postcode', ($this->canEdit() ? 'text' : 'label'), array(
                'label'     => $this->__('Zip'),
                'name'      => 'shipment[postcode]'
            ));
        }

        // Set dependencies for fields that are specific to shipment or production
        if ( $this->canEdit() ) {
            $this->setChild('form_after', $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
                ->addFieldMap("reference_type",      'reference_type')
                ->addFieldMap("shipment_box_count",  'shipment[box_count]')
                ->addFieldMap("shipment_street1",    'shipment[street][0]')
                ->addFieldMap("shipment_street2",    'shipment[street][1]')
                ->addFieldMap("shipment_city",       'shipment[city]')
                ->addFieldMap("shipment_country_id", 'shipment[country_id]')
                ->addFieldMap("shipment_region_id",  'shipment[region_id]')
                ->addFieldMap("shipment_postcode",   'shipment[postcode]')
                ->addFieldMap("production_cost",     'production[cost]')
                // Shipment fields
                ->addFieldDependence('shipment[box_count]',   'reference_type', 'shipment')
                ->addFieldDependence('shipment[street][0]',   'reference_type', 'shipment')
                ->addFieldDependence('shipment[street][1]',   'reference_type', 'shipment')
                ->addFieldDependence('shipment[city]' ,      'reference_type', 'shipment')
                ->addFieldDependence('shipment[country_id]', 'reference_type', 'shipment')
                ->addFieldDependence('shipment[region_id]',  'reference_type', 'shipment')
                ->addFieldDependence('shipment[postcode]',   'reference_type', 'shipment')
                // Production fields
                ->addFieldDependence('production[cost]',     'reference_type', 'production')
            );
        }

        $form->setValues($data);

        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getReferenceTypes()
    {
        return array(
            'shipment' => $this->__('Shipment'),
            'production' => $this->__('Print Job'),
            'returned_item' => $this->__('Loaned Item Return')
        );
    }

    public function canEdit()
    {
        return Mage::getSingleton('admin/session')->isAllowed(GlobalThinking_Inventory_Model_Stock_Receipt::ACL_UPDATE) ||
                Mage::getSingleton('admin/session')->isAllowed(GlobalThinking_Inventory_Model_Stock_Receipt::ACL_CREATE);
    }
}