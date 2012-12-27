<?php

/**
*
* @author jabi
*
*/
class KlearMatrix_Model_ScreenOption extends KlearMatrix_Model_AbstractOption
{
    // La opción es abrible en multi-instancia?
    protected $_multiInstance = false;

    // TODO
    // Define si es necesario seleccionar campos para ejecutar esta opción general
    protected $_fieldRelated = false;

    // Para comprobar en opciones desde columna... no permitir siempre que sea diferente al de la pantalla contenedora... (sino, lío de IDs)
    protected $_filterField = false;

    // Si se trata de una opción de pantalla "externa", en esta estructura se dejarán los atributos screen y file
    protected $_externalConfig = null;

    protected function _init()
    {
        $this->_type = 'screen';

        $this->_filterField = $this->_config->getProperty("filterField");
        $this->_multiInstance = (bool)$this->_config->getProperty("multiInstance");

        $this->_externalConfig = $this->_config->getProperty("external");
        if ($this->_externalConfig) {
            $this->setName($this->_externalConfig->screen);
        }
    }

    public function getFilterField()
    {
        return $this->_filterField;
    }

    public function getType() {

        return $this->_type;
    }

    public function toArray()
    {
        $ret = array(
            'icon' => $this->_class,
            'type' => 'screen',
            'screen' => $this->_name,
            'title' => $this->getTitle(),
            'label' => $this->_label,
            'defaultOption' => $this->isDefault(),
            'multiInstance' => $this->_multiInstance,
            'showOnlyOnNotNull' => $this->_showOnlyOnNotNull,
            'showOnlyOnNull' => $this->_showOnlyOnNull
        );

        if (!is_null($this->_externalConfig)) {
            $ret['externalOption'] = true;
            $ret['file'] = $this->_externalConfig->file;
        }

        return $ret;
    }
}
