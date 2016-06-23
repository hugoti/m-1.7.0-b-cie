<?php

class Cielo_Block_Form extends Mage_Payment_Block_Form_Cc {

  protected function _construct() {
    parent::_construct();
    $this->setTemplate('batman/cielo/form.phtml');
  }

    public function getCcAvailableTypes()
    {
		$_types = Mage::getConfig()->getNode('global/cielo/types')->asArray();

        $types = array();
        foreach ($_types as $data) {
            if (isset($data['code']) && isset($data['name'])) {
                $types[$data['code']]["codigo"] = $data['code'];
		$types[$data['code']]["nome"] = $data['name'];
		switch($data['code']) {
		  //Condições especiais (Visa Débito):
		  case "VD":
		    $types[$data['code']]["parcela"]["A"] = "Débito à Vista";
		    break;
		  //Condições especiais (Discovery só tem crédito à vista):
		  case "DS":
		    $types[$data['code']]["parcela"]["1"] = "Crédito à Vista";
		    break;
		  default:
		    $types[$data['code']]["parcela"] = $this->getParcelamento();
		    break;
		}
            }
        }
        
        return $types;
    }

    public function getParcelamento()
    {
	$_types = Mage::getConfig()->getNode('global/cielo/paymentsaccepted')->asArray();

        $types = array();
        foreach ($_types as $data) {
            if (isset($data['code']) && isset($data['name'])) {
                $types[$data['code']] = $data['name'];
            }
        }
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('paymentsaccepted');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code=>$name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
    }
}
?>
