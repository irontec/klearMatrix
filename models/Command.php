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
	
	
	public function setConfig(Zend_Config $config)
	{
        
	    $this->_config = new Klear_Model_KConfigParser;
	    $this->_config->setConfig($config);
	
	    $this->_mapper = $this->_config->getProperty("mapper",false);
	    $this->_modelFile = $this->_config->getProperty("modelFile",false);
	
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
	    
	    if ($this->_modelFile) {
	        $this->_parseModelFile();
	    }
	    
	    if ($this->_mapper) {
	        $this->_checkClasses(array("_mapper"));
	    }
	}
	
	
}