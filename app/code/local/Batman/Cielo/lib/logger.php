<?php
	class Logger
	{			 
		public function logWrite($strMessage, $transacao)
		{
			$path = $_SERVER["REQUEST_URI"];
			$data = date("Y-m-d H:i:s:u (T)");
			
			$log = "***********************************************" . "\n";
			$log .= $data . "\n";
			$log .= "DO ARQUIVO: " . $path . "\n"; 
			$log .= "OPERAÇÃO: " . $transacao . "\n";
			$log .= $strMessage . "\n\n"; 

			Mage::log($log);
		}
	}
?>
