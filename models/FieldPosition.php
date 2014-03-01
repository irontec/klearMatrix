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
    protected $_colsPerRow = "auto";

    public function setConfig($config)
    {

        $this->_config = new Klear_Model_ConfigParser;
        $this->_config->setConfig($config);

        $this->_label = $this->_config->getProperty("label");
        $this->_label = Klear_Model_Gettext::gettextCheck($this->_label);

        if ($this->_config->getProperty("colsPerRow")) {
            $this->_colsPerRow = $this->_config->getProperty("colsPerRow");
        }
        if (!isset($this->_config->getRaw()->fields)) {
            throw new \Klear_Exception_Default('No config found for "fields"');
        }
        
        $numberOfFields = 0;

        foreach ($this->_config->getRaw()->fields as $field => $active) {
            $boolActive = (bool)$active;
            if ($boolActive) {
                // We know it is active... maybe we have a number?
                $number = intval($active);
                
                if ($number == 0) {
                    $weight = 1;
                } else {
                    $weight = $number;
                }
                $this->_fields[] = array("field"=>$field,"weight"=>$weight);
                $numberOfFields = $numberOfFields + $weight ;
            
            }
        }
        
        if (intval($this->_colsPerRow) == 0) { // si no es un entero, es "auto", se autocalcula
            $this->_colsPerRow = $numberOfFields;
        }
        
    }

    public function toArray()
    {
        return array(
            'colsPerRow'=>$this->_colsPerRow,
            'label'=>$this->_label,
            'fields'=>$this->_fields
        );
    }

}