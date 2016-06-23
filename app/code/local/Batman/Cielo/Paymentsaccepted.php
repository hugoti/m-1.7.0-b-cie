<?php

class Cielo_Paymentsaccepted
{

    public function toOptionArray()
    {
        $_types = Mage::getConfig()->getNode('global/cielo/paymentsaccepted')->asArray();
  
  	$types = array();
        foreach ($_types as $data) {
            if (isset($data['code']) && isset($data['name'])) {
                $types[$data['code']] = $data['name'];
            }
        }
 
        $options =  array();

        foreach ($types as $code => $name) {
            $options[] = array(
               'value' => $code,
               'label' => $name
            );
        }
        return $options;
    }
}
