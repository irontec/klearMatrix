<?php
/**
* Clase para screens que extiende de KlearMatrix_Model_ResponseItem
*
* @author jabi
*/

class KlearMatrix_Model_Screen extends KlearMatrix_Model_ResponseItem
{

	protected $_type = 'screen';

    protected $_hooks = array();
    protected $_csv = false;

	protected $_configOptionsCustom = array(
        '_mapper' => array('mapper', true),
        '_modelFile' => array('modelFile', true),
	    '_csv' => array('csv', false),
	    '_hooks' => array('hooks', false)
	);

	//Seteamos el nombre del screen
	public function setScreenName($name)
	{
		$this->setItemName($name);
	}

	//Seteamos la configuración del screen
	public function setConfig(Zend_Config $config)
	{
	    //Mandamos $config al setConfig del padre, que guarda en $this->_config un objeto Klear_Model_KConfigParser
	    parent::setConfig($config);

	    //Seteamos la info de ayuda si la hubiese
	    $this->setInfo();
	}

	public function getCsv()
	{
        return $this->_csv;
	}

	public function getHooks()
	{
	    return $this->_hooks;
	}

	/**
	 * @return false | string $methodName
	 */
	public function getHook($hookName = null)
	{
	    if (is_null( $hookName ) or ! isset( $this->_hooks->$hookName )) {

	        return false;
	    }

	    return $this->_hooks->$hookName;
	}

}
