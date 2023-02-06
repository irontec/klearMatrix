<?php

/**
*
* @author jabi
*
*/
class KlearMatrix_Model_Option_Screen extends KlearMatrix_Model_Option_Abstract
{
    // La opción es abrible en multi-instancia?
    protected $_multiInstance = false;

    // TODO
    // Define si es necesario seleccionar campos para ejecutar esta opción general
    protected $_fieldRelated = false;

    // Para comprobar en opciones desde columna.
    // No permitir siempre que sea diferente al de
    // la pantalla contenedora... (sino, lío de IDs)
    protected $_filterField = false;

    // Si se trata de una opción de pantalla "externa",
    // en esta estructura se dejarán los atributos en
    // la lista de atributos permitidos + screen
    protected $_externalConfig = null;
    /**
     * Opciones external:
     * file: hace referencia al .yaml de sección al que se apunta
     * searchby:
     *      hace referencia al campo por el que se va a filtrar la pantalla
     *      (searchFields)
     * noiden:
     *      consigue desde un listado, no enviar por GET el PK
     *      (y enviarlo sólamente como valor a filtrar por searchby)
     * removescreen:
     *      elimina el screen de la petición
     *      (para "coincidir" con peticiones de pantallas del
     *      menu sidebary no duplicar pestañas)
     * title:
     *      el título para la pestaña; de manera que el tooltip
     *      pueda ser dinámico con %item%, pero el título sea "respetado"
     */
    protected $_allowedExternalAttrs = array(
        "file",
        "searchby",
        "noiden",
        "removescreen",
        "title");

    // Para hacer que una opción esté siempre habilitada aún cuando su comportamiento
    // por defecto sería no estarlo
    protected $_alwaysEnabled = false;

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

    public function getType()
    {
        return $this->_type;
    }

    public function getAlwaysEnabled()
    {
        return $this->_alwaysEnabled;
    }

    public function toArray()
    {

        $ret = $this->_prepareArray();

        $ret['screen'] = $this->_name;
        $ret['type'] = 'screen';
        $ret['multiInstance'] = $this->_multiInstance;



        if (!is_null($this->_externalConfig)) {
            $ret['externalOption'] = true;
            foreach ($this->_allowedExternalAttrs as $attr) {
                if (isset($this->_externalConfig->{$attr})) {
                    $ret['external' . $attr] = Klear_Model_Gettext::gettextCheck($this->_externalConfig->{$attr});
                }
            }
        }

        return $ret;
    }
}
