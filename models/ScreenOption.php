<?php

/**
*
* @author jabi
*
*/
class KlearMatrix_Model_ScreenOption 
{

    protected $_config;
    protected $_screen;
    protected $_class;
    protected $_title;
    protected $_showOnlyOnNotNull = false;
    protected $_showOnlyOnNull = false;
    
    // La opción es abrible en multi-instancia?
    protected $_multiInstance = false;

    // Define si es necesario seleccionar campos para ejecutar esta opción general
    // TODO
    protected $_fieldRelated = false;

    // Para comprobar en opciones desde columna... no permitir siempre que sea diferente al de la pantalla contenedora... (sino, lío de IDs)
    protected $_filterField = false;

    protected $_default = false;


    protected $_noLabel = true;

    public function setScreenName($screen) 
    {
        $this->_screen = $screen;
    }

    public function setConfig(Zend_Config $config) 
    {

        $this->_config = new Klear_Model_KConfigParser;
        $this->_config->setConfig($config);

        $this->_title = $this->_config->getProperty("title",false);
        $this->_filterField = $this->_config->getProperty("filterField",false);
        $this->_class = $this->_config->getProperty("class",false);
        $this->_label = (bool)$this->_config->getProperty("label",false);
        $this->_multiInstance = (bool)$this->_config->getProperty("multiInstance",false);
        $this->_showOnlyOnNotNull = (bool)$this->_config->getProperty("optionShowOnlyOnNotNull",false);
        $this->_showOnlyOnNull = (bool)$this->_config->getProperty("optionShowOnlyOnNull",false);
        

    }

    // Solo aplicable para fieldOPtionsWrapper
    public function setAsDefault() 
    {
        $this->_default = true;
    }

    public function isDefault() 
    {
        return true === $this->_default;
    }

    public function getFilterField() 
    {
        return $this->_filterField;
    }

    public function getTitle() 
    {
        if (null !== $this->_title) {
            return $this->_title;
        }

        // o_O pues eso.... MAL!
        return 'error';

    }

    public function toArray() 
    {
        return array(
            'icon'=>$this->_class,
            'type'=>'screen',
            'screen'=>$this->_screen,
            'title'=>$this->getTitle(),
            'label'=>$this->_label,
            'defaultOption'=>$this->isDefault(),
            'multiInstance'=>$this->_multiInstance,
            'showOnlyOnNotNull' => $this->_showOnlyOnNotNull,
            'showOnlyOnNull' => $this->_showOnlyOnNull
                
        );
    }



}
