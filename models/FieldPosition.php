<?php



/**
 * Estructura de mensajes a ejecutar antes o después de una acción
 * @author jabi
 *
 */
class KlearMatrix_Model_FieldPosition
{
    protected $_config;

    protected $_label;
    protected $_fields = array();

    public function setConfig($config)
    {

        $this->_config = new Klear_Model_ConfigParser;
        $this->_config->setConfig($config);

        $this->_label = $this->_config->getProperty("label");
        $this->_label = Klear_Model_Gettext::gettextCheck($this->_label);


        if (!isset($this->_config->getRaw()->fields)) {
            throw new \Klear_Exception_Default('No config found for "fields"');
        }

        foreach ($this->_config->getRaw()->fields as $field => $active) {
            $boolActive = (bool)$active;
            if ($boolActive) {
                // We know it is active... maybe we have a number?
                $number = intval($active);
                if ($number == 0) {
                    $this->_fields[]  = $field;
                    continue;
                } else {
                    for ($i=0;$i<$number;$i++) {
                        $this->_fields[]  = $field;
                    }
                }

            }
        }
    }

    public function toArray()
    {
        return array(
            'label'=>$this->_label,
            'fields'=>$this->_fields
        );
    }

}