<?php

class KlearMatrix_CloneController extends Zend_Controller_Action
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
            ->addActionContext('clone', 'json')
            ->initContext('json');

        $this->_mainRouter = $this->getRequest()->getUserParam("mainRouter");
        $this->_item = $this->_mainRouter->getCurrentItem();
    }


    public function indexAction()
    {
        
        $mapperName = $this->_item->getMapperName();
        $mapper = new $mapperName;
        $pk = $this->_mainRouter->getParam("pk");
        $this->_helper->log('Clone for mapper (not executed):' . $mapperName . ' > PK('.$pk.')');
        $obj = $mapper->find($pk);

        if (!$obj) {
            throw new Klear_Exception_Default($this->view->translate('Record not found. Could not clone.'));
        }

        if ($this->getRequest()->getPost("activate") == true) {
            return $this->_doTheAction();
        }

        $cols = $this->_item->getVisibleColumns();
        $defaultParentCol = $cols->getDefaultCol();
        $getter = 'get' . $obj->columnNameToVar($defaultParentCol->getDbFieldName());
        $name = $obj->$getter();

        if ($this->_item->getDescription()) {
            $message = $this->_item->getDescription();
        } else {
            $message = $this->view->translate('Seguro que quieres clonar esta entrada?');
        }
        $message .= '<p class="clonable-item">'
            . $this->view->translate('Título:')
            . '<strong>'.$name.'</strong> <em>(#'.$pk.')</em></p>';

        $data = array(
            'message' => $message,
            'title' => $this->view->translate($this->_item->getTitle()),
            'buttons' => array(
                'Clonar' => array(
                    'recall' => true,
                    'params' => array('activate'=>true)
                ),
                'Cancelar' => array(
                    'recall' => false,
                )
            )
        );

        $jsonResponse = new Klear_Model_DispatchResponse();
        $jsonResponse->setModule('klearMatrix');
        $jsonResponse->setPlugin('klearMatrixGenericDialog');
        $jsonResponse->addJsFile("/../klearMatrix/js/plugins/jquery.klearmatrix.genericdialog.js");
        $jsonResponse->setData($data);
        $jsonResponse->attachView($this->view);
    }

    protected function _doTheAction()
    {

        $mapperName = $this->_item->getMapperName();
        $mapper = new $mapperName;
        $pk = $this->_mainRouter->getParam("pk");
        $this->_helper->log('Clone::clone action for mapper:' . $mapperName . ' > PK('.$pk.')');
        $obj = $mapper->find($pk);

        try {
            if (!$obj) {
                $this->_helper->log(
                    'Error cloning model for ' . $mapperName . ' > PK('.$pk.')',
                    Zend_Log::ERR
                );
                throw new Klear_Exception_Default($this->view->translate('Record not found. Could not clone.'));
            }
            $newObj = clone $obj;
            $newObj->{"set".ucfirst($obj->columnNameToVar($obj->getPrimaryKeyName()))}(null);
            if (!$newObj->save()) {
                throw new Exception('Unknown error');
            }
        } catch (Exception $e) {
            $this->_helper->log(
                'Error cloning model for ' . $mapperName . ' > PK('.$pk.')',
                Zend_Log::ERR
            );
            throw new Klear_Exception_Default($this->view->translate('Could not clone record: ') . $e->getMessage());
        }

        $this->_helper->log('Model succesfully cloned for ' . $mapperName . ' > PK('.$pk.')');

        $data = array(
                'message' => $this->view->translate('Record successfully cloned'),
                'title' => $this->_item->getTitle(),
                'buttons' =>  array(
                    'Cerrar' => array(
                        'recall' => false,
                        'reloadParent' => true
                    )
                )
        );

        $jsonResponse = new Klear_Model_DispatchResponse();
        $jsonResponse->setModule('klearMatrix');
        $jsonResponse->setPlugin('klearMatrixGenericDialog');
        $jsonResponse->addJsFile("/../klearMatrix/js/plugins/jquery.klearmatrix.genericdialog.js");
        $jsonResponse->setData($data);
        $jsonResponse->attachView($this->view);
    }

}
