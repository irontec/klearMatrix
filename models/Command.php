<?php
/**
* Clase para commands que extiende de KlearMatrix_Model_ResponseItem
*
* @author jabi
*/

class KlearMatrix_Model_Command extends KlearMatrix_Model_ResponseItem
{

	protected $_type = 'command';

    protected $_configOptionsCustom = array();

	//Seteamos el nombre del command
	public function setCommandName($name)
	{
		$this->setItemName($name);
	}

    //Seteamos la configuración del screen
    public function setConfig(Zend_Config $config)
    {
        //Mandamos $config al setConfig del padre, que guarda en $this->_config un objeto Klear_Model_KConfigParser
        parent::setConfig($config);
    }

}