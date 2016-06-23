<?php

class Cielo_Subdivision
{

    public function toOptionArray()
    {
        return array(
            array('value' => '2 ', 'label'=>Mage::helper('adminhtml')->__('Lojista')),
            array('value' => '3', 'label'=>Mage::helper('adminhtml')->__('Administradora')),
        );
    }
}
