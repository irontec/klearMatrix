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

        /**
         * Initialize action controller here
         */
        $this->_helper->layout->disableLayout();

        $this->_helper->ContextSwitch()
            ->addActionContext('index', 'json')
            ->addActionContext('clone', 'json')
            ->initContext('json');

        $this->_mainRouter = $this->getRequest()->getUserParam('mainRouter');
        $this->_item = $this->_mainRouter->getCurrentItem();

    }

    public function indexAction()
    {

        $mapperName = $this->_item->getMapperName();
        $mapper = new $mapperName;
        $pk = $this->_mainRouter->getParam('pk');

        $this->_helper->log(
            'Clone for mapper (not executed):' . $mapperName . ' > PK('.$pk.')'
        );

        $obj = $mapper->find($pk);

        if (!$obj) {
            throw new Klear_Exception_Default(
                $this->view->translate('Record not found. Could not clone.')
            );
        }

        if ($this->getRequest()->getPost('activate') == true) {
            return $this->_doTheAction();
        }

        $cols = $this->_item->getVisibleColumns();
        $defaultParentCol = $cols->getDefaultCol();
        $getter = 'get' . $obj->columnNameToVar($defaultParentCol->getDbFieldName());
        $name = $obj->$getter();

        if ($this->_item->getDescription()) {
            $message = $this->_item->getDescription();
        } else {
            $message = sprintf(
                $this->view->translate('Do you want to clone this %s?'),
                $this->view->translate('record')
            );
        }

        $message .= '<p class="clonable-item">'
            . $this->view->translate('Title:')
            . '<strong>'.$name.'</strong> <em>(#'.$pk.')</em></p>';

        $title = $this->_item->getTitle();
        if (empty($title)) {
            $title = sprintf(
                $this->view->translate('Clone %s'),
                $this->view->translate('record')
            );
        }

        $data = array(
            'message' => $message,
            'title' => $this->_item->getTitle(),
            'buttons' => array(
                $this->view->translate('Clone') => array(
                    'recall' => true,
                    'params' => array(
                        'activate' => true
                    )
                ),
                $this->view->translate('Cancel') => array(
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

        $this->_helper->log(
            'Clone::clone action for mapper:' . $mapperName . ' > PK('.$pk.')'
        );

        $obj = $mapper->find($pk);

        try {

            if (!$obj) {
                $this->_helper->log(
                    'Error cloning model for ' . $mapperName . ' > PK('.$pk.')',
                    Zend_Log::ERR
                );

                throw new Klear_Exception_Default(
                    $this->view->translate('Record not found. Could not clone.')
                );
            }

            $newObj = $this->_cloneModel($obj);

            //$newObj->{"set".ucfirst($obj->columnNameToVar($obj->getPrimaryKeyName()))}(null);

            $forceValues = $this->_item
                ->getConfig()
                ->getProperty('cloneForceValues');

            if (is_object($forceValues)) {
                foreach ($forceValues as $key => $val) {
                    $newObj->{"set".ucfirst($key)}($val);
                }
            }

            if (!$newObj->save()) {
                throw new Exception('Unknown error');
            }

            $newPk = $newObj->getPrimaryKey();

            if ($this->_isCloneDependents()) {

                $mapperNameParts = explode("\\", $mapperName);
                $tableName = $mapperNameParts[count($mapperNameParts)-1];
                $relatedTables = $obj->getDependentList();

                foreach ($relatedTables as $relatedTable) {
                    if ($relatedTable['table_name'] === $tableName) {
                        continue;
                    }

                    $getter = 'get' . $relatedTable['property'];
                    $relatedModels = $obj->$getter();

                    foreach ($relatedModels as $relatedModel) {

                        $relatedColums = array();
                        $parentList = $relatedModel->getParentList();

                        foreach ($parentList as $parent) {
                            if (in_array($tableName, $parent)) {
                                $relatedColums[] = $parent["property"];
                            }
                        }

                        if (count($relatedColums) > 0) {

                            $this->_cloneFiles($relatedModel, $relatedModel);
                            foreach ($relatedColums as $relatedColum) {
                                $relatedValue = $relatedModel->{"get".$relatedColum}();
                                if ($relatedValue && $relatedValue->getPrimaryKey() == $obj->getPrimaryKey()) {
                                    $relatedModel->{"set".$relatedColum}($newObj);
                                }
                            }

                            $newRelModel = $this->_cloneModel($relatedModel);

                            if (!$newRelModel->save()) {
                                throw new Exception('Unknown error');
                            }

                        }
                    }

                }
            }

            if ($postCloneMethods = $this->_item->getConfig()->getProperty("postCloneMethods")) {
                $obj = $mapper->find($pk);
                $newObj = $mapper->find($newPk);
                $methods = array();
                foreach ($postCloneMethods as $model => $method) {
                    $methods[$model] = $method;
                }
                if (isset($methods["original"])) {
                    $obj->{$methods["original"]}($newObj);
                }
                if (isset($methods["clonned"])) {
                    $newObj->{$methods["clonned"]}($obj);
                }
            }

        } catch (Exception $e) {
            $this->_helper->log(
                'Error cloning model for ' . $mapperName . ' > PK('.$pk.')',
                Zend_Log::ERR
            );
            throw new Klear_Exception_Default($this->view->translate('Could not clone record: ') . $e->getMessage());
        }

        $this->_helper->log('Model succesfully cloned for ' . $mapperName . ' > PK('.$pk.')');

        if ($this->_item->getMessage()) {
            $message = $this->_item->getMessage();
        } else {
            $message = sprintf(
                $this->view->translate('%s successfully cloned'),
                $this->view->translate('Record')
            );
        }

        $data = array(
            'message' => $message,
            'title' => $this->_item->getTitle(),
            'buttons' =>  array(
                $this->view->translate('Close') => array(
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

    /**
     * Clona el modelo y si tiene ficheros tambien se clonan.
     */
    protected function _cloneModel($obj)
    {

        $objArray = $obj->toArray();
        $objArray[$obj->getPrimaryKeyName()] = null;

        $modelName = get_class($obj);
        $newModel = new $modelName();
        $newModel->setFromArray($objArray);

        $this->_cloneFiles($obj, $newModel);

        return $newModel;

    }

    /**
     * Copia los ficheros del modelo original al clon.
     */
    protected function _cloneFiles($obj, $newModel)
    {

        $files = $obj->getFileObjects();

        if (!empty($files)) {
            foreach ($files as $file) {
                $fetcher = 'fetch' . ucfirst($file);
                $putter = 'put' . ucfirst($file);
                $setterBasetname = 'set' . ucfirst($file) . 'BaseName';

                $getBasename = 'get' . ucfirst($file) . 'BaseName';
                $basename = $obj->{$getBasename}();

                $filePath = $obj->{$fetcher}()->getFilePath();
                $realPath = realpath($filePath);

                if (file_exists($filePath)) {
                    $tmpFile = tempnam(sys_get_temp_dir(), 'CLONE');

                    copy($realPath, $tmpFile);

                    $newModel->{$putter}(
                        $tmpFile,
                        $obj->{$fetcher}()->getBaseName()
                    );

                    $newModel->{$setterBasetname}($basename);

                }
            }
        }

    }

    /**
     * Clonar items hijos?
     */
    protected function _isCloneDependents()
    {

        $config = $this->_item->getConfig();

        if (empty($config->getProperty('cloneDependents'))) {
            return false;
        }

        if ($config->getProperty('cloneDependents') === true) {
            return true;
        }

        return false;

    }

}