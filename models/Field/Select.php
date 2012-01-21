<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/

class KlearMatrix_Model_Field_Select extends KlearMatrix_Model_Field_Abstract {

    protected $_adapter;
    
    public function init()
    {
        $config = $this->_column->getKlearConfig();
        
        $sourceConfig = $config->getRaw()->source;
        
        $adapterClassName = "KlearMatrix_Model_Field_Select_" . ucfirst($sourceConfig->data);
        
        $this->_adapter = new $adapterClassName;
        $this->_adapter
                    ->setConfig($sourceConfig)
                    ->init();
        
    }
    
    public function toArray() {
        return $this->_adapter;
    }
	
}