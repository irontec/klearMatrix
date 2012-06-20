<?php
/**
* Clase para dialogs que extiende de KlearMatrix_Model_ResponseItem
*
* @author jabi
*/

class KlearMatrix_Model_Dialog extends KlearMatrix_Model_ResponseItem
{

    protected $_type = 'dialog';

    protected $_configOptionsCustom = array();

    //Seteamos el nombre del dialog
    public function setDialogName($name)
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

}