<?php
/**
* Clase para commands que extiende de KlearMatrix_Model_ResponseItem
*
* @author jabi
*/

class KlearMatrix_Model_Command extends KlearMatrix_Model_ResponseItem
{

    protected $_type = 'command';

    //Seteamos el nombre del command
    public function setCommandName($name)
    {
        $this->setItemName($name);
    }
}