<?php

class Cielo_Environment
{

    public function toOptionArray()
    {
        return array(
            array('value' => 'Approval', 'label'=>Mage::helper('adminhtml')->__('Approval')),
            array('value' => 'Production', 'label'=>Mage::helper('adminhtml')->__('Production')),
        );
    }

}
