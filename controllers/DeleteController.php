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

        $this->_mainRouter = $this->getRequest()->getUserParam("mainRouter");
        $this->_item = $this->_mainRouter->getCurrentItem();
    }


    public function indexAction()
    {
        $mapperName = $this->_item->getMapperName();
        $mapper = new $mapperName;

        $pk = $this->_mainRouter->getParam("pk");

        $this->_helper->log('Delete for mapper (not executed):' . $mapperName . ' > PK('.$pk.')');

        $cols = $this->_item->getVisibleColumns();

        $defaultCol = $cols->getDefaultCol();

        $cols->clear()
             ->addCol($defaultCol);

        $data = new KlearMatrix_Model_MatrixResponse;

        $data->setColumnCollection($cols)
            ->setPK($this->_item->getPkName())
            ->setResponseItem($this->_item);

        $obj = $mapper->find($pk);
        if (!$obj) {
            throw new Klear_Exception_Default($this->view->translate('Record not found. Could not delete.'));
        }

        $data->setResults($obj);
        $data->fixResults($this->_item);

        $jsonResponse = KlearMatrix_Model_DispatchResponseFactory::build();
        $jsonResponse->setPlugin('delete');
        $jsonResponse->addTemplate("/template/delete/type/" . $this->_item->getType(), "klearmatrixDelete");
        $jsonResponse->addTemplateArray($cols->getTypesTemplateArray("/template/field/type/", "clearMatrixFields"));
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.delete.js");
        $jsonResponse->setData($data->toArray());
        $jsonResponse->attachView($this->view);
    }

    public function deleteAction()
    {

        $mapperName = $this->_item->getMapperName();
        $mapper = new $mapperName;

        $pk = $this->_mainRouter->getParam("pk");

        $this->_helper->log('Delete::delete action for mapper:' . $mapperName . ' > PK('.$pk.')');

        // TO-DO traducir mensaje?
        // Recuperamos el objeto y realizamos la acción de borrar

        $obj = $mapper->find($pk);

        try {
            if (!$obj) {
                $this->_helper->log(
                    'Error deleting model for ' . $mapperName . ' > PK('.$pk.')',
                    Zend_Log::ERR
                );
                throw new Klear_Exception_Default($this->view->translate('Record not found. Could not delete.'));
            }

            if (!$obj->delete()) {
                throw new Exception('Unknown error');
            }
        } catch (Exception $e) {
            $this->_helper->log(
                'Error deleting model for ' . $mapperName . ' > PK('.$pk.')',
                Zend_Log::ERR
            );
            throw new Klear_Exception_Default($this->view->translate('Could not delete record: ') . $e->getMessage());
        }

        $this->_helper->log('model succesfully deleted for ' . $mapperName . ' > PK('.$pk.')');

        $data = array(
            'error' => false,
            'pk' => $pk,
            'message' => $this->view->translate('Registro eliminado correctamente')
        );


        $jsonResponse = new Klear_Model_SimpleResponse();
        $jsonResponse->setData($data);
        $jsonResponse->attachView($this->view);
    }

}
