<?php
class KlearMatrix_Model_ParentOptionCustomizer_Response
{
    protected $_result;
    protected $_wrapper;
    protected $_cssClass;

    public function setResult($data)
    {
        $this->_result = $data;
        return $this;
    }

    public function getResult()
    {
        return $this->_result;
    }

    public function setWrapper($data)
    {
        $this->_wrapper = $data;
        return $this;
    }

    public function getWrapper()
    {
        return $this->_wrapper;
    }

    public function setCssClass($data)
    {
        $this->_cssClass = $data;
        return $this;
    }

    public function getCssClass()
    {
        return $this->_cssClass;
    }

    public function toArray()
    {
        return array(
            'result'  => $this->_result,
            'wrapper' => $this->_wrapper,
            'class'   => $this->_cssClass,
        );
    }
}
