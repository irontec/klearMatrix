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

    protected $_parentOptionCustomizers = array();

    public function setConfig(Zend_Config $config)
    {
        $this->_config = new Klear_Model_ConfigParser;
        $this->_config->setConfig($config);

        $this->_title = $this->_config->getProperty("title");
        $this->_class = $this->_config->getProperty("class");
        $this->_label = (bool)$this->_config->getProperty("label");
        $this->_showOnlyOnNotNull = (bool)$this->_config->getProperty("optionShowOnlyOnNotNull");
        $this->_showOnlyOnNull = (bool)$this->_config->getProperty("optionShowOnlyOnNull");

        $this->_loadParentOptionCustomizers($this->_config->getProperty("parentOptionCustomizer"));

        $this->_init();
    }

    public function getConfig() {

        return $this->_config;
    }

    /**
     * @param Zend_Config | string | null parentOptionCustomizerClasses
     */
    protected function _loadParentOptionCustomizers($parentOptionCustomizerClasses)
    {
        if (is_null($parentOptionCustomizerClasses)) {

            return null;
        }

        if (is_string($parentOptionCustomizerClasses)) {

            $parentOptionCustomizerClasses = array($parentOptionCustomizerClasses);
        }

        foreach ($parentOptionCustomizerClasses as $className) {

            $optionCustomizerClass = $this->_getParentOptionCustomizerClass($className);

            if (!$optionCustomizerClass) {
                continue;
            }

            $realClassName = get_class($optionCustomizerClass);


            $classNameSegments = explode("_", $realClassName);
            $lastClassNameSegment = end($classNameSegments);

            $optionCustomizerClass->setOption($this);
            $this->_parentOptionCustomizers[$lastClassNameSegment] = $optionCustomizerClass;
        }
    }

    protected function _getParentOptionsCustomizerClass($optionClassName)
    {
        $className = $optionClassName;

        if (!$optionClassName) {
            return null;
        }

        if ($optionClassName instanceof Zend_Config) {
            $className = $className->key();
        }

        if (!$class_exists($className)) {

            $className = 'KlearMatrix_Model_ParentOptionCustomizer_' . ucfirst($className);

            if (!class_exists($className)) {

                throw new Exception($className . " not found");
            }
        }

        $parameters = $this->_getParentOptionsCustomizerParameters($optionClassName);

        $class = $className($parameters);

        if (!$class instanceof KlearMatrix_Model_Interfaces_ParentOptionCustomizer) {

            throw new Exception($realClassName . " does not implement KlearMatrix_Model_Interfaces_ParentOptionCustomizer");
        }

        return $class;
    }

    protected function _getParentOptionsCustomizerParameters($optionClassName)
    {
        if (!$optionClassName instanceof Zend_Config) {
            return new Zend_Config(array());
        }

        return $className->current();
    }

    protected function _init()
    {
    }

    public function musBeAltered()
    {
        return (count($this->_parentOptionCustomizers) > 0);
    }

    /**
     * @return array | null
     */
    public function customizeParentOption($model)
    {
        foreach ($this->_parentOptionCustomizers as $name => $parser) {

            $values = $parser->customize($model);

            if ($values instanceof KlearMatrix_Model_ParentOptionCustomizer_Response) {

                return array(
                    $this->_name => $values->toArray()
                );
            }
        }

        return null;
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