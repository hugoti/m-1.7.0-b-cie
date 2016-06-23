<?php

class Cielo_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action {
  public function indexAction()
  {
          $this->loadLayout();
          $this->renderLayout();
  }
  public function consultarAction() {
	$orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
	
	$this->consultar($order);
	$this->_redirect("adminhtml/sales_order/view/",array('order_id'=>$orderId));

  }

  public function capturarAction() {
	$orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
	
	$this->capturar($order);
	$this->_redirect("adminhtml/sales_order/view/",array('order_id'=>$orderId));
  }

  public function autorizarAction() {
	$orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
	
	$this->autorizar($order);
	$this->_redirect("adminhtml/sales_order/view/",array('order_id'=>$orderId));
  }

  public function cancelarAction() {
	$orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
	
	$this->cancelar($order);
	$this->_redirect("adminhtml/sales_order/view/",array('order_id'=>$orderId));
  }

  private function getPedido($order) {
	
	$payment = $order->getPayment();
	
    	//Configura a URL da Cielo:
	$ExternalLibPath=Mage::getModuleDir('', 'Cielo') . DS . 'lib' . DS .'include.php';
	require_once ($ExternalLibPath);

	$Pedido = new Pedido();
	$Pedido->tid = $payment->getCcTransId();
	$Pedido->dadosEcNumero = CIELO;
	$Pedido->dadosEcChave = CIELO_CHAVE;

	return $Pedido;
  }

  private function consultar($order) {
	
	$payment = $order->getPayment();

	$Pedido = $this->getPedido($order);
	
	// Atualiza status
	$objResposta = $Pedido->RequisicaoConsulta();
	$statusAntigo = $payment->getCcStatus;
	$Pedido->status = $objResposta->status;

	//Sempre que consulta, verifica se atualiza o status:
	if($payment->getCcStatus != $Pedido->status) {
	  $payment->setCcStatus($Pedido->status);
	  $payment->save();

	  $order->addStatusToHistory($order->getStatus(), 'Status da transação Cielo atualizado para <b>' . $Pedido->getStatus() . '</b>.', false);
	  $order->save();
	}

	$this->alterarStatusPedido($order, $Pedido);

	return $Pedido;
  }

  private function capturar($order) {
	
	$payment = $order->getPayment();

	$Pedido = $this->getPedido($order);
	
	$objResposta = $Pedido->RequisicaoCaptura(null, null);
	$Pedido->status = $objResposta->status;
	$payment->setCcStatus($Pedido->status);
	$payment->save();

	$order->addStatusToHistory($order->getStatus(), 'Ação de captura na transação Cielo com retorno: '. $Pedido->getStatus() . '.', false);
	$order->save();

	$this->alterarStatusPedido($order, $Pedido);
	
	return $Pedido;
  }

  private function autorizar($order) {
	
	$payment = $order->getPayment();

	$Pedido = $this->getPedido($order);
	
	$objResposta = $Pedido->RequisicaoAutorizacaoTid();
	$Pedido->status = $objResposta->status;
	$payment->setCcStatus($Pedido->status);
	$payment->save();

	$order->addStatusToHistory($order->getStatus(), 'Ação de autorização na transação Cielo com retorno: '. $Pedido->getStatus() . '.', false);
	$order->save();

	$this->alterarStatusPedido($order, $Pedido);
	
	return $Pedido;
  }

  private function cancelar($order) {
	
	$payment = $order->getPayment();

	$Pedido = $this->getPedido($order);
	
	$objResposta = $Pedido->RequisicaoCancelamento();
	$Pedido->status = $objResposta->status;
	$payment->setCcStatus($Pedido->status);
	$payment->save();

	$order->addStatusToHistory($order->getStatus(), 'Ação de cancelamento na transação Cielo com retorno: '. $Pedido->getStatus() . '.', false);
	$order->save();

	$this->alterarStatusPedido($order, $Pedido); 
	
	return $Pedido;
  }

  private function alterarStatusPedido($order, $Pedido) {
	
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
  $order->save();
  }

}
