<?php

class Cielo_Block_Info extends Mage_Payment_Block_Info_Cc
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('batman/cielo/default.phtml');
    }

    public function getCcTypeName()
    {
        $_types = Mage::getConfig()->getNode('global/cielo/types')->asArray();
        $ccType = $this->getInfo()->getCcType();
        foreach ($_types as $data) {
	    if($data['code'] == $ccType) {
	       return $data['name'];
	    }
        }
        return Mage::helper('payment')->__('N/A');
    }

    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }

	$Pedido = $this->getPedido();

	switch(trim($this->getInfo()->getCcSsIssue())) {
	    case "A": $textoParcela = " Débito à vista";break;
	    case "1": $textoParcela = " Crédito à vista";break;
	    default: $textoParcela = " (".$this->getInfo()->getCcSsIssue()."x)";break;
	}

        $transport = parent::_prepareSpecificInformation($transport);
	$data = array();
        if ($ccType = $this->getCcTypeName()) {
            $data[Mage::helper('payment')->__('Credit Card Type')] = $ccType;
        }
	// Ignora os dados do pai:	
	$transport->setData($data);
	$transport->addData(array(
		Mage::helper('payment')->__('Forma de Pagamento') => $textoParcela,
                Mage::helper('payment')->__('Status atual') => $Pedido->getStatus(),
            ));        
	if (!$this->getIsSecureMode()) {
	    $transport->addData(array(
		Mage::helper('payment')->__('Identificador da transação') => $Pedido->tid,
            ));        

	}
        return $transport;
    }
    
    /** ID da compra para os botões da Cielo */
    public function getOrderId() {
	return $this->getInfo()->getEntityId();
    }

    private function getPedido() {
	//Configura a URL da Cielo:
	$ExternalLibPath=Mage::getModuleDir('', 'Cielo') . DS . 'lib' . DS .'include.php';
	require_once ($ExternalLibPath);

	$info = $this->getInfo();

	$Pedido = new Pedido();
	$Pedido->tid = $info->getCcTransId();
	$Pedido->status = $info->getCcStatus();
	$Pedido->dadosEcNumero = Mage::getStoreConfig('payment/cielo/shop_code');
	$Pedido->dadosEcChave = Mage::getStoreConfig('payment/cielo/shop_autentication_key');

	return $Pedido;
    }

    public function getConsultarUrl()
    {
	return Mage::helper("adminhtml")->getUrl("cielo/adminhtml_index/consultar/",array('order_id'=>$this->getOrderId()));
    }

    public function getCancelarUrl()
    {
	return Mage::helper("adminhtml")->getUrl("cielo/adminhtml_index/cancelar/",array('order_id'=>$this->getOrderId()));
    }

    public function getAutorizarUrl()
    {
	return Mage::helper("adminhtml")->getUrl("cielo/adminhtml_index/autorizar/",array('order_id'=>$this->getOrderId()));
    }

    public function getCapturarUrl()
    {
	return Mage::helper("adminhtml")->getUrl("cielo/adminhtml_index/capturar/",array('order_id'=>$this->getOrderId()));
    }



	/** --------------------- MÉDOTOS DE CONTROLE DE VISIBILIDADE DOS BOTÕES ----------------------- */

    /** Somente se a transação estiver autorizada ou capturada */
    public function podeCancelar() {
	$Pedido = $this->getPedido();
	$status = $Pedido->status;
	if($status == 4 || $status == 6)
		return true;
	
	return false;
    }

    /** Somente se a transação estiver autenticada */
    public function podeAutorizar() {
	$Pedido = $this->getPedido();
	$status = $Pedido->status;
	if($status == 2)
		return true;
	
	return false;
    }

    /** Somente se a transação estiver autorizada */
    public function podeCapturar() {
	$Pedido = $this->getPedido();
	$status = $Pedido->status;
	if($status == 4)
		return true;
	
	return false;
    }

    public function podeConsultar() {
	return true;
    }

}
