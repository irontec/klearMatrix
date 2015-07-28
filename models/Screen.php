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
    protected $_autoRefresh = false;

    protected $_configOptionsCustom = array(
        '_mapper' => array('mapper', false),
        '_modelFile' => array('modelFile', false),
        '_csv' => array('csv', false),
        '_autoRefresh' => array('autoRefresh', false),
        '_hooks' => array('hooks', false)
    );

    //Seteamos la configuración del screen
    protected function _initCustomConfig()
    {
        //Añadimos las configuraciones personalizadas
        foreach ($this->_configOptionsCustom as $config => $option) {

            $this->_configOptions[$config] = $option;
        }

        //Seteamos la info de ayuda si la hubiese
        $this->setInfo();
    }

    /**
     * Devuelve si está especificado la propiedad csv: true ó csv->active: true
     *
     * @return boolean
     */
    public function getCsv()
    {
        if (is_object($this->_csv)
             && get_class($this->_csv) == 'Zend_Config') {

            $retValue = (bool)$this->_csv->active;

        } else {
            $retValue = $this->_csv;
        }

        return $this->_csv;
    }

    public function getCsvParameters()
    {
        $csvOptions = array(
                "active" => false,
                "headers" => false,
                "ignoreBlackList" => false,
                "nameklear" => false,
                "filename" => "export",
                "newLine" => "\n",
                "enclosure" => '"',
                "separator" => ";",
                "encoding" => "utf-8",
                "executionSeconds" => false,
                "rawValues" => false
        );

        if (is_object($this->_csv) && get_class($this->_csv) == 'Zend_Config') {

            foreach ($csvOptions as $option => $value) {
                $value; //Avoid PMD UnusedLocalVariable warning
                if (isset($this->_csv->{$option})) {
                    $csvOptions[$option] = $this->_csv->{$option};
                }
            }
        }
        return $csvOptions;
    }

    public function getAutoRefresh()
    {
        return $this->_autoRefresh;
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
        if (is_null($hookName) || !isset( $this->_hooks->$hookName)) {
            return false;
        }

        return $this->_hooks->$hookName;
    }

}
