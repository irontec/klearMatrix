<?php

class KlearMatrix_Model_OptionsWrapper implements \IteratorAggregate
{
    protected $_opts = array();
    protected $_title;
    protected $_position;


    public function addOption($opt)
    {
        $this->_opts[] = $opt;

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
