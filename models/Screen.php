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
        '_mapper' => array('mapper', false),
        '_modelFile' => array('modelFile', false),
        '_csv' => array('csv', false),
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
        if (is_null($hookName) || !isset( $this->_hooks->$hookName)) {
            return false;
        }

        return $this->_hooks->$hookName;
    }

}
