<?php
abstract class KlearMatrix_Model_AbstractOption
{
    use Klear_Model_Trait_Gettext;
    
    protected $_config;
    protected $_class;
    protected $_title;
    protected $_default = false;
    protected $_noLabel = true;

    protected $_name;

    protected $_showOnlyOnNotNull = false;
    protected $_showOnlyOnNull = false;

    public function setConfig(Zend_Config $config)
    {
        $this->_config = new Klear_Model_ConfigParser;
        $this->_config->setConfig($config);

        $this->_title = $this->_config->getProperty("title");
        $this->_class = $this->_config->getProperty("class");
        $this->_label = (bool)$this->_config->getProperty("label");
        $this->_showOnlyOnNotNull = (bool)$this->_config->getProperty("optionShowOnlyOnNotNull");
        $this->_showOnlyOnNull = (bool)$this->_config->getProperty("optionShowOnlyOnNull");

        $this->_init();
    }

    protected function _init()
    {
    }

    /**
     * Set option's name
     * @param unknown_type $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    public function getTitle()
    {
        if (null != $this->_title) {
            
            
            return $this->_gettextCheck($this->_title);
        }

        return '';
    }

    // Solo aplicable para fieldOptionsWrapper
    public function setAsDefault()
    {
        $this->_default = true;
    }

    public function isDefault()
    {
        return true === $this->_default;
    }

    abstract public function toArray();
}