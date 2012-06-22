<?php

class KlearMatrix_DeleteController extends Zend_Controller_Action
{

    /**
     * Route Dispatcher desde klear/index/dispatch
     * @var KlearMatrix_Model_RouteDispatcher
     */
    protected $_mainRouter;

    /**
     * Screen|Dialog
     * @var KlearMatrix_Model_ResponseItem
     */
    protected $_item;

    public function init()
    {
        /* Initialize action controller here */
        $this->_helper->layout->disableLayout();

        $this->_helper->ContextSwitch()
            ->addActionContext('index', 'json')
            ->addActionContext('delete', 'json')
            ->initContext('json');

        $this->_mainRouter = $this->getRequest()->getParam("mainRouter");
        $this->_item = $this->_mainRouter->getCurrentItem();
    }


    public function indexAction()
    {
        $mapperName = $this->_item->getMapperName();
        $mapper = new $mapperName;

        $pk = $this->_mainRouter->getParam("pk");

        $cols = $this->_item->getVisibleColumnWrapper();

        $defaultCol = $cols->getDefaultCol();

        $cols
            ->resetWrapper()
            ->addCol($defaultCol);

        $data = new KlearMatrix_Model_MatrixResponse;

        $data->setColumnWraper($cols)
            ->setPK($this->_item->getPkName())
            ->setResponseItem($this->_item);

        if (!$obj = $mapper->find($pk)) {
            // Error

        } else {
            $data->setResults($obj);
            $data->fixResults($this->_item);
        }

        $jsonResponse = new Klear_Model_DispatchResponse();
        $jsonResponse->setModule('klearMatrix');
        $jsonResponse->setPlugin('delete');
        $jsonResponse->addTemplate("/template/delete/type/" . $this->_item->getType(), "klearmatrixDelete");
        $jsonResponse->addTemplateArray($cols->getTypesTemplateArray("/template/field/type/", "clearMatrixFields"));
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.template.helper.js");
        $jsonResponse->addJsFile("/js/translation/jquery.klearmatrix.translation.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.module.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.delete.js");
        $jsonResponse->addCssFile("/css/klearMatrixEdit.css");
        $jsonResponse->setData($data->toArray());
        $jsonResponse->attachView($this->view);
    }


    public function deleteAction()
    {

        $mapperName = $this->_item->getMapperName();
        $mapper = new $mapperName;

        $pk = $this->_mainRouter->getParam("pk");


        // TO-DO traducir mensaje?
        // TO-DO lanzar excepción ?
        // Recuperamos el objeto y realizamos la acción de borrar

        $obj = $mapper->find($pk);

        if ($obj && $obj->delete()) {

            $data = array(
                'error' => false,
                'pk' => $pk,
                'message' => 'Registro eliminado correctamente'
            );

        } else {

            $data = array(
                'error' => true,
                'message' => 'Algún error eliminado el registro'
            );
        }

        $jsonResponse = new Klear_Model_SimpleResponse();
        $jsonResponse->setData($data);
        $jsonResponse->attachView($this->view);
    }

}