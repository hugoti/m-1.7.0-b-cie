<?php

$code = 'cielo';

// specify title for this payment method
$this->addConfigField('payment/'.$code.'/title', 'Title');
$this->setConfigData('payment/'.$code.'/title', 'Cielo');

// add Enabled flag configuration
$this->addConfigField('payment/'.$code.'/active', 'Enabled', array(
'frontend_type'=>'select',
'source_model'=>'adminhtml/system_config_source_payment_active'
));

// choose initial order status when checking out with this payment method
$this->addConfigField('payment/'.$code.'/order_status', 'Order Status', array(
'frontend_type'=>'select',
'source_model'=>'adminhtml/system_config_source_order_status'
));

// set position for this payment method in list
$this->addConfigField('payment/'.$code.'/sort_order', 'Sort Order');

$installer = $this;

$installer->startSetup();

$installer->endSetup();

?>
