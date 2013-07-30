<?php

class KlearMatrix_Model_FieldPositionCollection implements \IteratorAggregate
{
    protected $_positions = array();

    public function getIterator()
    {
        return new \ArrayIterator($this->_msgs);
    }

    public function count()
    {
        return sizeof($this->_positions);
    }
    public function addPosition(KlearMatrix_Model_FieldPosition $pos)
    {
        $this->_positions[] = $pos;
    }

    public function toArray()
    {
        $retArray = array();

        foreach ($this->_positions as $pos) {
            $retArray[] = $pos->toArray();
        }
        return $retArray;
    }
}