<?php

class KlearMatrix_Model_ActionMessageCollection implements \IteratorAggregate
{
    protected $_msgs = array();
    protected $_position;


    public function getIterator()
    {
        return new \ArrayIterator($this->_msgs);
    }

    public function addMessage(KlearMatrix_Model_ActionMessage $msg)
    {

        $this->_msgs[] = $msg;
    }

    public function toArray()
    {
        $retArray = array();

        foreach ($this->_msgs as $msg) {
            $type = $msg->getType();

            if ( !isset($retArray[$type]) ) {

                $retArray[$msg->getType()] = array();
            }

            $retArray[$msg->getType()][] = $msg->toArray();
        }

        return $retArray;
    }
}