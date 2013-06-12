<?php

class KlearMatrix_Model_OptionCollection implements \IteratorAggregate
{
    const DEFAULT_PLACEMENT = 'bottom';

    protected $_opts = array();
    protected $_title;

    private $_acceptedValues = array('top', 'bottom', 'both');

    protected $_optionsPlacement;

    public function __construct()
    {
    }

    public function addOption(KlearMatrix_Model_AbstractOption $opt)
    {
        $this->_opts[] = $opt;

    }

    public function setPlacement($placement, $module = 'default')
    {
        if (in_array(strtolower($placement), $this->_acceptedValues)) {
            $this->_optionsPlacement[$module] = strtolower($placement);
        }
        return $this;
    }

    public function getPlacement($module = 'default')
    {
        if (!isset($this->_optionsPlacement)) {
            $this->_initOptionsPlacement();
        }

        if (isset($this->_optionsPlacement[$module])) {
            return $this->_optionsPlacement[$module];
        }

        return $this->_optionsPlacement['default'];
    }

    protected function _initOptionsPlacement()
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $siteConfig = $bootstrap->getResource('modules')->offsetGet('klear')->getOption('siteConfig');
        $placement = $siteConfig->getDefaultCustomConfiguration('optionCollectionPlacement');

        $this->setPlacement(self::DEFAULT_PLACEMENT, 'default');
        
        if (!$placement) {
            $placement = self::DEFAULT_PLACEMENT;
        }

        if ($placement instanceof \Zend_Config) {
            foreach ($placement as $module => $value) {
                $this->setPlacement($value, $module);
            }
        } else {
            $this->setPlacement($placement, 'default');
        }
    }

    public function toArray()
    {
        $retArray = array();
        foreach ($this->_opts as $opt) {
            $retArray[] = $opt->toArray();
        }

        return $retArray;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->_opts);
    }
}
