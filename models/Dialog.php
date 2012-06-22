<?php
/**
* Clase para dialogs que extiende de KlearMatrix_Model_ResponseItem
*
* @author jabi
*/

class KlearMatrix_Model_Dialog extends KlearMatrix_Model_ResponseItem
{

    protected $_type = 'dialog';

    //Seteamos el nombre del dialog
    public function setDialogName($name)
    {
        $this->setItemName($name);
    }

    //Seteamos la configuración del screen
    protected function _initCustomConfig()
    {
        //Seteamos la info de ayuda si la hubiese
        $this->setInfo();
    }

}