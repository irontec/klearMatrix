<?php
abstract class KlearMatrix_Model_AbstractOption
{
    protected $_config;
    protected $_class;
    protected $_title;
    protected $_default = false;

    protected $_label = true;
    protected $_labelOnEdit = true;
    protected $_labelOnList = true;
    protected $_labelOnEntityPostSave = true;

    protected $_from = "auto";

    protected $_shortcut = true;

    protected $_multiItem = false;

    protected $_name;

    protected $_showOnlyOnNotNull = false;
    protected $_showOnlyOnNull = false;

    protected $_parentHolderSelector = false;

    protected $_parentOptionCustomizers = array();

    /**
     * List of attributes
     * @var array
     */
    protected $_attribs = array(
            'label',
            'labelOnEdit',
            'labelOnList',
            'labelOnEntityPostSave',
            'shortcut',
            'multiItem',
            'showOnlyOnNotNull',
            'showOnlyOnNull',
            'parentHolderSelector',
            'from'
    );

    public function setConfig(Zend_Config $config)
    {


        $this->_config = new Klear_Model_ConfigParser;
        $this->_config->setConfig($config);

        $this->_class = $this->_config->getProperty("class");


        $this->_title = $this->_config->getProperty("title");

        $this->_setLabel($this->_config->getProperty("label"));
        $this->_setLabelOnEdit($this->_config->getProperty("labelOnEdit"));
        $this->_setLabelOnList($this->_config->getProperty("labelOnList"));
        $this->_setLabelOnEntityPostSave($this->_config->getProperty("labelOnEntityPostSave"));

        $this->_shortcut = substr($this->_config->getProperty("shortcutOption"), 0, 1);

        $this->_multiItem = (bool)$this->_config->getProperty("multiItem");
        $this->_showOnlyOnNotNull = (bool)$this->_config->getProperty("optionShowOnlyOnNotNull");
        $this->_showOnlyOnNull = (bool)$this->_config->getProperty("optionShowOnlyOnNull");

        // Propiedad que indica al lanzador de la acción desde donde coger el ID (enviado al request)
        // No tiene nada que ver con parentOptionCustomizer
        $this->_parentHolderSelector = $this->_config->getProperty("parentHolderSelector");

        $this->_loadParentOptionCustomizers($this->_config->getProperty("parentOptionCustomizer"));

        $this->_init();
    }

    protected function _parseLabelConfig($value)
    {

        $filteredValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if (is_null($filteredValue)) {
            return Klear_Model_Gettext::gettextCheck($value);
        }

        if ($filteredValue === true) {
            return $this->getTitle();
        } else {
            return false;
        }

    }

    public function getConfig()
    {
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
            $optionCustomizerClass = $this->_getParentOptionsCustomizerClass($className);

            $realClassName = get_class($optionCustomizerClass);
            $this->_parentOptionCustomizers[md5($realClassName)] = $optionCustomizerClass;
        }
    }

    protected function _getParentOptionsCustomizerClass($className)
    {
        $optionCustomizerClassName = $this->_getParentOptionsCustomizerClassName($className);
        $optionCustomizerParameters = $this->_getParentOptionsCustomizerParameters($className);
        $optionCustomizerClass = new $optionCustomizerClassName($optionCustomizerParameters);

        if (!$optionCustomizerClass instanceof KlearMatrix_Model_Interfaces_ParentOptionCustomizer) {

            throw new Exception($className . " does not implement KlearMatrix_Model_Interfaces_ParentOptionCustomizer");
        }

        $optionCustomizerClass->setOption($this);
        return $optionCustomizerClass;

    }

    protected function _getParentOptionsCustomizerClassName($optionClassName)
    {
        $className = $optionClassName;

        if (!$optionClassName) {
            return null;
        }

        if ($optionClassName instanceof Zend_Config) {
            $className = $className->key();
        }

        if (!class_exists($className)) {

            $className = 'KlearMatrix_Model_ParentOptionCustomizer_' . ucfirst($className);

            if (!class_exists($className)) {

                throw new Exception($className . " not found");
            }
        }

        return $className;
    }

    protected function _getParentOptionsCustomizerParameters($optionClassName)
    {
        if (!$optionClassName instanceof Zend_Config) {
            return new Zend_Config(array());
        }

        return $optionClassName->current();
    }

    protected function _init()
    {
    }

    public function mustCustomize()
    {
        return (count($this->_parentOptionCustomizers) > 0);
    }

    /**
     * @return array | null
     */
    public function customizeParentOption($model)
    {
        foreach ($this->_parentOptionCustomizers as $parser) {

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

    public function getName()
    {
        return $this->_name;
    }

    public function getTitle()
    {
        if (null != $this->_title) {
            return Klear_Model_Gettext::gettextCheck($this->_title);
        }
        return '';
    }

    /**
     *
     * @param unknown $title
     * @return KlearMatrix_Model_AbstractOption
     */
    public function setTitle($title)
    {
        $this->_title = $title;
        return $this;
    }

    /**
     *
     * @param unknown $class
     * @return KlearMatrix_Model_AbstractOption
     */
    public function setClass($class)
    {
        $this->_class = $class;
        return $this;
    }

    /**
     *
     * @param unknown $shortcut
     * @return KlearMatrix_Model_AbstractOption
     */
    public function setShortcut($shortcut)
    {
        $this->_shortcut = $shortcut;
        return $this;
    }

    /**
     *
     * @param unknown $label
     * @return KlearMatrix_Model_AbstractOption
     */
    protected function _setLabel($label)
    {
        $this->_label = $this->_parseLabelConfig($label);
        return $this;
    }

    /**
     *
     * @param unknown $labelOnEdit
     * @return KlearMatrix_Model_AbstractOption
     */
    protected function _setLabelOnEdit($labelOnEdit)
    {
        $this->_labelOnEdit = $this->_parseLabelConfig($labelOnEdit);
        return $this;
    }

    /**
     *
     * @param unknown $labelOnList
     * @return KlearMatrix_Model_AbstractOption
     */
    protected function _setLabelOnList($labelOnList)
    {
        $this->_labelOnList = $this->_parseLabelConfig($labelOnList);
        return $this;
    }

    /**
     *
     * @param unknown $labelOnEntityPostSave
     * @return KlearMatrix_Model_AbstractOption
     */
    protected function _setLabelOnEntityPostSave($labelOnEntityPostSave)
    {
        $this->_labelOnEntityPostSave = $this->_parseLabelConfig($labelOnEntityPostSave);
        return $this;
    }

    /**
     *
     * @param unknown $label
     * @return KlearMatrix_Model_AbstractOption
     */
    public function setLabel($label)
    {
        $this->_setLabel($label);
        return $this;
    }

    /**
     *
     * @param unknown $labelOnEdit
     * @return KlearMatrix_Model_AbstractOption
     */
    public function setLabelOnEdit($labelOnEdit)
    {
        $this->_setLabelOnEdit($labelOnEdit);
        return $this;
    }

    /**
     *
     * @param unknown $labelOnList
     * @return KlearMatrix_Model_AbstractOption
     */
    public function setLabelOnList($labelOnList)
    {
        $this->_setLabelOnList($labelOnList);
        return $this;
    }

    /**
     *
     * @param unknown $labelOnEntityPostSave
     * @return KlearMatrix_Model_AbstractOption
     */
    public function setLabelOnEntityPostSave($labelOnEntityPostSave)
    {
        $this->_setLabelOnEntityPostSave($labelOnEntityPostSave);
        return $this;
    }

    public function setFrom($from)
    {
        $this->_from = $from;
    }

    /**
     *
     * @param unknown $multiItem
     * @return KlearMatrix_Model_AbstractOption
     */
    public function setMultiItem($multiItem)
    {
        $this->_multiItem = $multiItem;
        return $this;
    }

    /**
     *
     * @param unknown $showOnlyOnNotNull
     * @return KlearMatrix_Model_AbstractOption
     */
    public function setShowOnlyOnNotNull($showOnlyOnNotNull)
    {
        $this->_showOnlyOnNotNull = $showOnlyOnNotNull;
        return $this;
    }

    /**
     *
     * @param unknown $showOnlyOnNull
     * @return KlearMatrix_Model_AbstractOption
     */
    public function setShowOnlyOnNull($showOnlyOnNull)
    {
        $this->_showOnlyOnNull = $showOnlyOnNull;
        return $this;
    }

    // Solo aplicable para fieldOptionsWrapper
    public function setAsDefault()
    {
        $this->_default = true;
        return $this;
    }

    public function isDefault()
    {
        return true === $this->_default;
    }

    protected function _removeFalse($ret)
    {
        foreach ($ret as $idx => $value) {
            if (false === $value) {
                unset($ret[$idx]);
            }
        }
        return $ret;

    }

    /**
     * Especifica a la opción, en que "tag" buscar el contenedor de si id (data-id)
     * @param unknown_type $selector
     */
    public function setParentHolderSelector($selector)
    {
        $this->_parentHolderSelector = $selector;
    }

    /**
     * Valores Comunes a todas las opciones
     * @return multitype:boolean string NULL
     */
    protected function _prepareArray()
    {

        $ret = array(
                'icon' => $this->_class,
                'title' => $this->getTitle(),
                'defaultOption' => $this->isDefault()
        );

        foreach ($this->_attribs as $attribute) {
            $prop = '_' . $attribute;
            if (!is_null($this->{$prop})) {
                $ret[$attribute] = $this->{$prop};
            }
        }

//         var_dump($ret); exit;
        return $ret;

    }

    abstract public function toArray();

    public function toAutoOption()
    {
        $ret = '<span class="autoOption" ';
        foreach($this->toArray() as $prop => $value) {
            $prop = ltrim(strtolower(preg_replace('/[A-Z]/', '-$0', $prop)), '-');
            $ret .= 'data-' . $prop . '="' . $value.'" ';
        }
        $ret .= '></span>';
        return $ret;
    }
}