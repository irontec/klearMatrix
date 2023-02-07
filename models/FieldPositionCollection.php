<?php

class KlearMatrix_Model_FieldPositionCollection implements \IteratorAggregate
{
    protected $_msgs = [];
    protected $_positions = [];

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->_msgs);
    }

    public function count()
    {
        return count($this->_positions);
    }
    public function addPosition(KlearMatrix_Model_FieldPosition $pos)
    {
        $this->_positions[] = $pos;
    }

    public function toArray()
    {
        $retArray = [];

        foreach ($this->_positions as $pos) {
            $retArray[] = $pos->toArray();
        }
        return $retArray;
    }
}
