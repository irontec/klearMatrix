<?php
class KlearMatrix_Model_ParentOptionCustomizer_Response
{
    protected $_result;
    protected $_wrapper;
    protected $_parentWrapper;
    protected $_cssClass;
    protected $_parentCssClass;

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
    
    public function setParentWrapper($data)
    {
        $this->_parentWrapper = $data;
        return $this;
    }
    
    public function getParentWrapper()
    {
        return $this->_parentWrapper;
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
    
    public function setParentCssClass($data)
    {
        $this->_parentCssClass = $data;
        return $this;
    }
    
    public function getParentCssClass()
    {
        return $this->_parentCssClass;
    }

    public function toArray()
    {
        return array(
            'result'  => $this->_result,
            'wrapper' => $this->_wrapper,
            'parentWrapper' => $this->_parentWrapper,
            'class'   => $this->_cssClass,
            'parentClass'   => $this->_parentCssClass,
        );
    }
}
