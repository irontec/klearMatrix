<?php



/**
 * Estructura de mensajes a ejecutar antes o después de una acción
 * @author jabi
 *
 */
class KlearMatrix_Model_ActionMessage
{

    protected $_type;
    protected $_message;
    protected $_actions;
   /*
    *      title:
    *      	i18n:
    *      		es: AVISO!
    *      message:
            i18n:
              es: Esta acción reiniciará su terminal.<br />¿Desea continuar?

          actions:
            ok:
              i18n:
                es: Si
              return: true
            cancel:
              i18n:
                es: No
              return: false*/


    public function setType($type)
    {
        switch($type) {
            case "before":
            case "after":
                   $this->_type = strtolower($type);
                   return $this;
        }

        Throw new Exception('Invalid Type for message');

    }

    public function setConfig($config) {






    }


}