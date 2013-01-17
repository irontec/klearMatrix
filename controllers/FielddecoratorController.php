<?php
/**
 * @author mikel
 *
 */
class KlearMatrix_FielddecoratorController extends Zend_Controller_Action
{
    public function init()
    {
        /* Initialize action controller here */
        $this->_helper->layout->disableLayout();
    }

    /**
     * TODO: Imágen por defecto si el modelo no existe
     * TODO: Default preview de default
     * TODO: Sistema de cacheo
     */
    public function indexAction()
    {
        $fieldDecoratorClassName = 'KlearMatrix_Model_Field_' .
                                ucfirst($this->_request->getParam("field")) . '_Decorator_' .
                                ucfirst($this->_request->getParam("fielddecorator"));

        if ( !class_exists($fieldDecoratorClassName)) {

            Throw new Exception("Field decorator $fieldDecoratorClassName not found");
        }

        $plugin = new $fieldDecoratorClassName($this->_request, $this->_helper, $this->view);
        $plugin->run();
    }
}
