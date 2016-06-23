<?php


class Cielo_PaymentController extends Mage_Core_Controller_Front_Action {

  public function redirectAction() {
    $this->loadLayout();
    $this->renderLayout();
  }

  public function returnAction() {
	$Pedido = $this->consultar();
	echo '<script type="text/javascript">parent.location = "'. Mage::getUrl('checkout/onepage/success', array('_secure'=>true)) . '"</script>';
  }

  public function iframeAction() {	

	$Pedido = $this->requisicaoTransacao();
	
	// Construct the redirection form
	$form = new Varien_Data_Form(array(
	'id' => 'cielo_iframe',
	'action' => $Pedido->urlAutenticacao,
	'name' => 'cielo_iframe',
	'method' => 'POST'));

	$form->setUseContainer(true);

	// Craft the HTML and return
	$html = $form->toHtml();
	$html .= '<script type="text/javascript">document.getElementById("cielo_iframe").submit();</script>';
	$html  = '<html><body>' . $this->__('Estamos processando sua solicitação, você será redirecionado à página de preenchimento dos dados do cartão em alguns instantes...') . '<br><br>' . $html .  '</body></html>';

	echo $html;
  }

  public function getBandeira($codigo) {
    switch($codigo) {
	case "VI":
	case "VD":
	  return "visa";
	case "MC":
	  return "mastercard";
	case "EL":
	  return "elo";
	case "DI":
	  return "diners";
	case "DS":
	  return "discover";
    }
  }

  private function requisicaoTransacao() {
	$session = Mage::getSingleton('checkout/session');
	$order = Mage::getModel('sales/order')->loadbyIncrementId($session->getLastRealOrderId());
	$payment = $order->getPayment();
	
    	//Configura a URL da Cielo:
	$ExternalLibPath=Mage::getModuleDir('', 'Cielo') . DS . 'lib' . DS .'include.php';
	require_once ($ExternalLibPath);

	$Pedido = new Pedido();
	$Pedido->formaPagamentoBandeira = $this->getBandeira($payment->getCcType());
	$parcelas = $payment->getCcSsIssue();
	
	if($parcelas != "A" && $parcelas != "1")
	{
		$Pedido->formaPagamentoProduto = Mage::getStoreConfig('payment/cielo/subdivision');
		$Pedido->formaPagamentoParcelas = $parcelas;
	} 
	else 
	{
		$Pedido->formaPagamentoProduto = $parcelas;
		$Pedido->formaPagamentoParcelas = 1;
	}

	$Pedido->dadosEcNumero = CIELO;
	$Pedido->dadosEcChave = CIELO_CHAVE;
	
	$Pedido->capturar = (Mage::getStoreConfig('payment/cielo/confirm'))?"true":"false";
	$Pedido->autorizar = 2;//Mage::getStoreConfig('payment/cielo/fill');
	
	$Pedido->dadosPedidoNumero = $order->getIncrementId(); 
	$Pedido->dadosPedidoValor = number_format($order->getBaseGrandTotal(),2,'','');

	$Pedido->urlRetorno = Mage::getUrl('*/*/return', array('_secure'=>true));

	// ENVIA REQUISIÇÃO SITE CIELO
	$objResposta = $Pedido->RequisicaoTransacao(false);
	
	$Pedido->tid = $objResposta->tid;
	$Pedido->pan = $objResposta->pan;
	$Pedido->status = $objResposta->status;

	$urlAutenticacao = "url-autenticacao";
	$Pedido->urlAutenticacao = $objResposta->$urlAutenticacao;

	//Registra a transação na compra:
	$data = $order->getData();
	if (!empty($data)) {
	  $payment->setCcTransId($Pedido->tid);
	  $payment->setCcStatus($Pedido->status);
	  $payment->save();

	  switch($parcelas) {
	    case "A": $textoParcela = " Débito à vista";break;
	    case "1": $textoParcela = " Crédito à vista";break;
	    default: $textoParcela = " (".$parcelas."x)";break;
	  }

	  $order->addStatusToHistory($order->getStatus(), 'Solicitação de pagamento enviada à Cielo: ' . $Pedido->formaPagamentoBandeira . $textoParcela. ' <b>TID:' . $Pedido->tid . '</b>', false);
	  $order->save();
	}

	return $Pedido;
  }

  private function getPedido() {

	$session = Mage::getSingleton('checkout/session');
	$order = Mage::getModel('sales/order')->loadbyIncrementId($session->getLastRealOrderId());
	$payment = $order->getPayment();

	if ($order == null || $order->getIncrementId() != $session->getLastRealOrderId()) {
	  return null;
	}
	
    	//Configura a URL da Cielo:
	$ExternalLibPath=Mage::getModuleDir('', 'Cielo') . DS . 'lib' . DS .'include.php';
	require_once ($ExternalLibPath);

	$Pedido = new Pedido();
	$Pedido->tid = $payment->getCcTransId();
	$Pedido->dadosEcNumero = CIELO;
	$Pedido->dadosEcChave = CIELO_CHAVE;

	return $Pedido;
  }

  private function consultar() {
	$session = Mage::getSingleton('checkout/session');
	$order = Mage::getModel('sales/order')->loadbyIncrementId($session->getLastRealOrderId());
	
  	if ($order == null || $order->getIncrementId() != $session->getLastRealOrderId()) {
	  return null;
	}


    	$payment = $order->getPayment();

	$Pedido = $this->getPedido();
	
	// Atualiza status
	$objResposta = $Pedido->RequisicaoConsulta();
	$statusAntigo = $payment->getCcStatus;
	$Pedido->status = $objResposta->status;

	//Sempre que consulta, verifica se atualiza o status:
	if($payment->getCcStatus != $Pedido->status) {
	  $payment->setCcStatus($Pedido->status);
	  $payment->save();

	  //Se estiver configurado, altera o status do pedido de acordo com o status da Cielo:
	  if(Mage::getStoreConfig('payment/cielo/alterar_status_pagamento')) {
	    switch($Pedido->status) {
		case 3:
		case 5:
		case 8:
		case 9:
		  $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
		  break;
		case 1:
		case 2:
		case 4:
		case 10:
		  $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
		  break;
		case 6:
		  $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
		  if($_order->canInvoice()) {
			/**
			* Create invoice
			* The invoice will be in 'Pending' state
			*/
			$invoiceId = Mage::getModel('sales/order_invoice_api')
			->create($order->getIncrementId(), array());

			$invoice = Mage::getModel('sales/order_invoice')
			->loadByIncrementId($invoiceId);

			/**
			* Pay invoice
			* i.e. the invoice state is now changed to 'Paid'
			*/
			$invoice->capture()->save();
		  }
		  break;


	    }
	  }

	  $order->addStatusToHistory($order->getStatus(), 'Status da transação Cielo alterada de : ' . $statusAntigo . '  para <b>' . $Pedido->getStatus() . '</b>.', false);
	  $order->save();
	}

	return $Pedido;
  }

}
