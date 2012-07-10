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
    *          i18n:
    *              es: AVISO!
    *      message:
            i18n:
              es: Esta acción reiniciará su terminal.<br />¿Desea continuar?

          actions:
            ok:
              label
                i18n:
                  es: Si
              return: true
            cancel:
              label:
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

    public function getType()
    {
        return $this->_type;
    }

    public function setConfig($config)
    {

        $this->_config = new Klear_Model_ConfigParser;
        $this->_config->setConfig($config);

        $this->_title = $this->_config->getProperty("title");
        $this->_message = $this->_config->getProperty("message");

        $_actions = $this->_config->getRaw()->actions;


        foreach ($_actions as $idx => $_action) {

           $parsedAction = new Klear_Model_ConfigParser;
           $parsedAction->setConfig($_action);
           $aAction = array(
                       'label' => $parsedAction->getProperty('label'),
                       'return' => (bool)$parsedAction->getProperty('return')
                   );

           if (!$aAction['label']) {
               $aAction['label'] = $idx;
           }

           $this->_action[]= $aAction;
        }
    }

    public function toArray()
    {

        return array(
                'message'=>$this->_message,
                'type'=>$this->_type,
                'title'=>$this->_title,
                'action'=>$this->_action
                );
    }

}