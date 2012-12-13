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

    protected $_alterClasses = array();

    public function setConfig(Zend_Config $config)
    {

        $this->_config = new Klear_Model_ConfigParser;
        $this->_config->setConfig($config);

        $this->_title = $this->_config->getProperty("title");
        $this->_class = $this->_config->getProperty("class");
        $this->_label = (bool)$this->_config->getProperty("label");
        $this->_showOnlyOnNotNull = (bool)$this->_config->getProperty("optionShowOnlyOnNotNull");
        $this->_showOnlyOnNull = (bool)$this->_config->getProperty("optionShowOnlyOnNull");

        $this->_loadAlterClasses($this->_config->getProperty("alter"));

        $this->_init();
    }

    public function getConfig() {

        return $this->_config;
    }

    /**
     * @param Zend_Config | null $alterClasses
     */
    protected function _loadAlterClasses($alterClasses) {

        $response = array();

        if (!$alterClasses instanceof Zend_Config) {

            return $response;
        }

        if (! isset($alterClasses->options)) {

            return $response;
        }

        foreach ($alterClasses->options as $className) {

            if (is_null($className) || empty($className)) {

                return null;
            }

            if (! class_exists($className)) {

                if (! class_exists('KlearMatrix_Model_Alter_Options_' . ucfirst($className))) {

                    Throw new Exception($className . " not found");
                }

                $className = 'KlearMatrix_Model_Alter_Options_' . ucfirst($className);
            }

            $classNameSegments = explode("_", $className);
            $lastClassNameSegment = end($classNameSegments);

            $class = new $className;

            if (! $class instanceof KlearMatrix_Model_Interfaces_Alter) {

                Throw new Exception($className . " does not implement KlearMatrix_Model_Interfaces_Alter");
            }

            $class->setOption($this);
            $this->_alterClasses[$lastClassNameSegment] = $class;
        }
    }

    protected function _init()
    {

    }

    public function musBeAltered() {

        return count($this->_alterClasses) > 0 ? true : false;
    }

    public function alterResult($model) {

        $response = array(
        );

        foreach ($this->_alterClasses as $name => $parser) {

            $values = $parser->alter($model);

            if (! is_null($values)) {

                $response[$this->_name] = $values;
            }
        }

        return $response;
    }

    /**
     * Set option's name
     * @param unknown_type $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    public function getName() {

        return $this->_name;
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