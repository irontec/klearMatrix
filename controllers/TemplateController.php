<?php

class KlearMatrix_TemplateController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
        $this->_helper->layout->disableLayout();
    }

    public function listAction()
    {
    }

    public function editAction()
    {
    }

    public function newAction()
    {
    }

    public function paginatorAction()
    {
    }


    public function deleteAction()
    {
    }

    public function multilangAction()
    {
        $templateTypes = array('list', 'field');
        $templateItem = $this->getRequest()->getParam("item");

        if (in_array($templateItem, $templateTypes)) {
            $this->_helper->viewRenderer('multilang/' . $templateItem);
        }
    }

    public function fieldAction()
    {
        $fieldType = $this->getRequest()->getParam("type");

        if (in_array($fieldType, $this->_getAvailableFieldTypes())) {
            $this->_helper->viewRenderer('fields/' . $fieldType);
        }
    }

    protected function _getAvailableFieldTypes()
    {
        return array(
            "text",
            "textarea",
            "select",
            "multiselect",
            "password",
            "number",
            "ghost",
            "file",
            "picker",
        );
    }
}