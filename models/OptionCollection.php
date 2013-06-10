<?php

class KlearMatrix_Model_OptionCollection implements \IteratorAggregate
{
    protected $_opts = array();
    protected $_title;
    
    private $_acceptedValues = array('top', 'bottom', 'both');
    
    protected $_optionsPlacement = 'bottom';

    public function __construct()
    {
        
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $siteConfig = $bootstrap->getResource('modules')->offsetGet('klear')->getOption('siteConfig');
        
        $this->setPlacement($siteConfig->getDefaultCustomConfiguration('optionCollectionPlacement'));
        
    }
    
    public function addOption(KlearMatrix_Model_AbstractOption $opt)
    {
        $this->_opts[] = $opt;

    }

    public function setPlacement($placement)
    { 
        if (in_array(strtolower($placement), $this->_acceptedValues)) {
            $this->_optionsPlacement = strtolower($placement);
        }
        return $this;
    }
    
    public function getPlacement()
    {
        return $this->_optionsPlacement;
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
