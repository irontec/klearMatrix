<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/
class KlearMatrix_Model_Screen extends KlearMatrix_Model_ResponseItem  {

	protected $_type = 'screen';

	
	public function setScreenName($name) {
		$this->setItemName($name);
	}

	public function setConfig(Zend_Config $config)
	{
	    $this->_config = new Klear_Model_KConfigParser;
	    $this->_config->setConfig($config);
	
	    $this->_mapper = $this->_config->getProperty("mapper",true);
	    $this->_modelFile = $this->_config->getProperty("modelFile",true);
	
	    $this->_filteredField = $this->_config->getProperty("filterField",false);
	
	    $this->_filterClass = $this->_config->getProperty("filterClass",false);
	
	    $this->_forcedValues = $this->_config->getProperty("forcedValues",false);
	
	    $this->_forcedPk = $this->_config->getProperty("forcedPk",false);
	
	    $this->_calculatedPkConfig = $this->_config->getProperty("calculatedPk",false);
	
	    $this->_plugin = $this->_config->getProperty("plugin", false);
	
	    $this->_title = $this->_config->getProperty("title",false);
	    
	    $this->_hooks = $this->_config->getProperty("hooks", array());
	    
	    $this->_customTemplate = $this->_config->getProperty("template", false);
	
	    $this->_customScripts = $this->_config->getProperty("scripts", false);
	
	    $this->_actionMessages = $this->_config->getProperty("actionMessages", false);
	
	    $this->_hasInfo = (bool)$this->_config->getProperty("info", false);
	    if ($this->_hasInfo) {
	        $this->_fieldInfo = new KlearMatrix_Model_Info;
	        $this->_fieldInfo->setConfig($this->_config->getProperty("info",false));
	    }
	
	    $this->_parseModelFile();
	    $this->_checkClasses(array("_mapper"));
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