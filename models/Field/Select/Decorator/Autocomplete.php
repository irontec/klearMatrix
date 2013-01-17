<?php
class KlearMatrix_Model_Field_Select_Decorator_Autocomplete extends KlearMatrix_Model_Field_DecoratorAbstract
{
    protected function _init()
    {
        $this->_helper->ContextSwitch()
                      ->addActionContext('index', 'json')
                      ->initContext('json');
    }

    public function run()
    {
        $this->_view->prueba = $this->_request->getParams();
    }
}