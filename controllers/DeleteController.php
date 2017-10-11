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
        $dataGateway = \Zend_Registry::get('data_gateway');
        $entity = $this->_item->getEntityClassName();

        $pk = $this->_mainRouter->getParam("pk");

        if (is_array($pk)) {
            $this->_helper->log('Delete for entity (not executed):' . $entity . ' > various PK('.implode(",", $pk).')');
        } else {
            $this->_helper->log('Delete for entity (not executed):' . $entity . ' > PK('.$pk.')');
            $pk = array($pk);
        }

        $cols = $this->_item->getVisibleColumns();
        $defaultCol = $cols->getDefaultCol();

        $cols->clear()
             ->addCol($defaultCol);

        $data = new KlearMatrix_Model_MatrixResponse;

        $data->setColumnCollection($cols)
            ->setPK($this->_item->getPkName())
            ->setResponseItem($this->_item);

        $parentScreenName = $this->getRequest()->getPost("parentScreen", false);
        if (!$parentScreenName) {
            $parentScreenName = $this->getRequest()->getPost("callerScreen", false);
        }

        if (false !== $parentScreenName) {
            $data->calculateParentData($this->_mainRouter, $parentScreenName, $pk);
        }

        $entityName = $this->_item->getEntityName();
        $where = [
            $entityName . '.id in ('. implode(",", $pk) .')'
        ];
        $result = $dataGateway->findBy($entity, $where);

        $data->setResults($result);
        $data->fixResults($this->_item);
        $data->parseItemAttrs($this->_item);

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
        $dataGateway = \Zend_Registry::get('data_gateway');
        $entity = $this->_item->getEntityClassName();

        $pk = $this->_mainRouter->getParam("pk");

        if (is_array($pk)) {
            $this->_helper->log('Delete::delete action for entity:' . $entity . ' > various PK('.implode(',', $pk).')');
        } else {
            $this->_helper->log('Delete::delete action for mapper:' . $entity . ' > PK('.$pk.')');
            $pk = array($pk);
        }

        if ($GLOBALS['sf']) {
            try {
                $dataGateway->remove($entity, $pk);
            } catch (\Exception $e) {
                $this->_helper->log(
                    'Error deleting model for ' . $entity . ' > PK('. $e->getCode() .')',
                    Zend_Log::ERR
                );
                throw new Klear_Exception_Default($this->view->translate('Could not delete record: ') . $e->getMessage());
            }


        } else if (!$GLOBALS['sf']) {

            $baseModel = $this->_item->getObjectInstance();
            $primaryKeyFieldName = $mapper->getDbTable()->getAdapter()->quoteIdentifier($baseModel->getPrimaryKeyName());
            $results = $mapper->fetchList($primaryKeyFieldName . " in ('".implode("','", $pk)."')");

            try {
                if (!is_array($results) || sizeof($results) == 0) {
                    $this->_helper->log(
                        'Error deleting model for ' . $mapperName . ' > PK('.$pk.')',
                        Zend_Log::ERR
                    );
                    throw new Klear_Exception_Default($this->view->translate('Record not found. Could not delete.'));
                }

                foreach ($results as $obj) {
                    if (!$obj->delete()) {
                        throw new Exception('Unknown error');
                    }
                }

            } catch (Exception $e) {
                $this->_helper->log(
                    'Error deleting model for ' . $mapperName . ' > PK('.$obj->getPrimaryKey().')',
                    Zend_Log::ERR
                );
                throw new Klear_Exception_Default($this->view->translate('Could not delete record: ') . $e->getMessage());
            }
        }

        $this->_helper->log('model succesfully deleted for ' . $entity . ' > PK('.implode(',', $pk).')');
        $data = array(
            'error' => false,
            'pk' => $pk,
        );

        if ($this->_item->getMessage()) {
            $data['message'] = str_replace("%total%", sizeof($pk), $this->_item->getMessage());
        } else {
            // Mensaje por defecto.
            $data['message'] = sprintf(
                $this->view->translate('(%d) %s successfully deleted'),
                sizeof($results),
                $this->view->translate('Record')
            );
        }

        $jsonResponse = new Klear_Model_SimpleResponse();
        $jsonResponse->setData($data);
        $jsonResponse->attachView($this->view);
    }
}
