<?php

class Cielo_Model_PaymentMethod extends Mage_Payment_Model_Method_Cc {
	protected $_code                    = 'cielo';
	protected $_formBlockType 	        = 'cielo/form';
	protected $_infoBlockType 	        = 'cielo/info';
	protected $_canAuthorize 	        = true;
	protected $_isGateway		        = true;
	protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc 		        = false;

	public function validate() {
	    
	    return $this;
	}

	public function getOrderPlaceRedirectUrl()
	{
	    return Mage::getUrl('cielo/payment/redirect', array('_secure' => true));
	}
}

?>
