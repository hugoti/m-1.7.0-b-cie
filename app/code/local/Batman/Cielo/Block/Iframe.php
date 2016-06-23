<?php

class Cielo_Block_Payment extends Mage_Payment_Block_Form_Cc {

  protected function _construct() {
    parent::_construct();
    $this->setTemplate('batman/cielo/payment.phtml');
  }

  protected function getIframeURL() {
    $session = Mage::getSingleton('checkout/session');
    $order = Mage::getModel('sales/order')->loadbyIncrementId($session->getLastRealOrderId());

    return $order->getData();
  }
}
?>
