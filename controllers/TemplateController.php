<?php

class KlearMatrix_TemplateController extends Zend_Controller_Action
{
    protected $_availabeFields = array(
            "checkbox",
            "file",
            "ghost",
            "multiselect",
            "number",
            "password",
            "picker",
            "radio",
            "select",
            "text",
            "textarea",
            "video",
            "map",
            "html5"
    );

    public function init()
    {
        /* Initialize action controller here */
        $this->_helper->layout->disableLayout();

        $this->_helper->ContextSwitch()
            ->addActionContext('cache', 'json')
            ->initContext('json');
    }

    public function cacheAction()
    {
        $cacheTemplates = array(
            "klearmatrixList" => "list",
            "klearmatrixEdit" => "edit",
            "klearmatrixNew" => "new",
            "klearmatrixPaginator" => "paginator",
            "klearmatrixDelete" => "delete",
            "klearmatrixDashboard" => "dashboard"
        );
        /**
         * Field type templates :)
         */
        $prefix = "klearMatrixFields";
        foreach ($this->_availabeFields as $type) {
            $cacheTemplates[$prefix . $type] = '/fields/' . $type;
        }

        /**
         * Cache them all!!
         */

        $templates = array();
        $this->view->setBasePath($this->getFrontController()->getModuleDirectory() . '/views');
        foreach ($cacheTemplates as $template => $action) {
            $templates[$template] = $this->view->render('template/' .  $action . '.phtml');
        }

        $this->view->templates = $templates;

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

    public function dashboardAction()
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

        if (in_array($fieldType, $this->_availabeFields)) {
            $this->_helper->viewRenderer('fields/' . $fieldType);
        }
    }

}