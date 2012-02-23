<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/
class KlearMatrix_Model_Command extends KlearMatrix_Model_ResponseItem {
	
	protected $_type = 'command';
	
	public function setCommandName($name) {
		$this->setItemName($name);
	}	
}