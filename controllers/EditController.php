<?php

class KlearMatrix_EditController extends Zend_Controller_Action
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
            ->addActionContext('save', 'json')
            ->initContext('json');

        $this->_mainRouter = $this->getRequest()->getUserParam("mainRouter");
        $this->_item = $this->_mainRouter->getCurrentItem();

        if ('save' === $this->getRequest()->getActionName()) {

            $this->_item->setIgnoreMetadataBlacklist(true);
        }
    }

    public function saveAction()
    {
        $mapperName = $this->_item->getMapperName();
        $mapper = \KlearMatrix_Model_Mapper_Factory::create($mapperName);


        // En el método save ya viaja el pk recalculado desde la pantalla de edición.
        $this->_item->unsetCalculatedPk();

        $pk = $this->_item->getCurrentPk();
        $this->_helper->log('edit::save action for mapper:' . $mapperName . ' > PK('.$pk.')');

        // TODO: traducir mensaje?
        $model = $mapper->find($pk);

        // TODO: traducir mensaje?
        $modelSpec = $this->_item->getModelSpec();
        $model = $modelSpec->setPrimaryKey($pk)->getInstance();

        if (!$model) {
            $this->_helper->log('PK NOT found in edit::save for ' . $mapperName . ' > PK('.$pk.')', Zend_Log::CRIT);
            throw new Zend_Exception($this->view->translate('Record not found.'));
        }

        $columns = $this->_item->getVisibleColumns(false, $model);
        $hasDependant = false;

        foreach ($columns as $column) {
            $this->_helper->Column2Model($model, $column);

            // Si una de las columnas tienen dependencias,
            // el save deberá llevar "saveRecursive"
            $hasDependant = $hasDependant || $column->isDependant();
        }

        try {
            $this->_save($model, $hasDependant);
            $this->_helper->log(
                'model save succesfully for ' . $mapperName . ' > PK('.$pk.')'
            );
            $data = array(
                'error' => false,
                'pk' => $model->getPrimaryKey(),
                'message' => $this->view->translate('Record successfully saved.')
            );
        } catch (\Zend_Exception $exception) {
            $data = array(
                'error' => true,
                'message'=> $exception->getMessage()
            );

            $this->_helper->log(
                'Error saving in edit::save for ' . $mapperName . ' > PK('.$pk.') ['.$exception->getMessage().']',
                Zend_Log::CRIT
            );
        }

        $jsonResponse = new Klear_Model_SimpleResponse();
        $jsonResponse->setData($data);
        $jsonResponse->attachView($this->view);
    }

    protected function _save($model, $hasDependant)
    {
        try {
            if (method_exists($model, 'saveRecursive')) {
                if ($hasDependant) {
                    $model->saveRecursive();
                } else {
                    $model->save();
                }
            } else {
                $model->save(false, $hasDependant);
            }
        } catch (\Zend_Exception $exception) {
            $this->_helper->log(
                'Error saving record: ' . $exception->getMessage()
            );
            $displayErrors = (ini_get("display_errors"));
            $message = $this->view->translate('Error saving record');
            if ($displayErrors) {
                $message.= " (".$exception->getMessage().")";
            }
            throw new \Zend_Exception($message);
        }
    }

    public function indexAction()
    {
        $pk = $this->_item->getCurrentPk();
        $mapperName = $this->_item->getMapperName();

        $this->_helper->log('Edit for mapper:' . $mapperName . ' > PK('.$pk.')');

        $mapper = \KlearMatrix_Model_Mapper_Factory::create($mapperName);
        $model = $mapper->find($pk);

        if (!$model) {

            $this->_helper->log('PK NOT FOUND ' . $mapperName . ' > PK('.$pk.')', Zend_Log::ERR);
            throw new Klear_Exception_Default($this->view->translate('Element not found. Cannot edit.'));
        }

        $this->_addConditionalBlackList($model);

        $columns = $this->_item->getVisibleColumns(false, $model);

        $data = new KlearMatrix_Model_MatrixResponse;

        $data
            ->setTitle($this->_item->getTitle())
            ->setColumnCollection($columns)
            ->setPK($this->_item->getPkName())
            ->setResponseItem($this->_item);

        $data->setResults($model)
             ->fixResults($this->_item);

        $parentScreenName = $this->getRequest()->getPost("parentScreen", false);
        if (!$parentScreenName) {
            $parentScreenName = $this->getRequest()->getPost("callerScreen", false);
        }

        if (false !== $parentScreenName) {
            $data->calculateParentData($this->_mainRouter, $parentScreenName, $pk);
        }

        $data->parseItemAttrs($this->_item);

        $jsonResponse = KlearMatrix_Model_DispatchResponseFactory::build();
        $jsonResponse->setPlugin($this->_item->getPlugin('edit'));

        $customTemplate = $this->_item->getCustomTemplate();

        if (isset($customTemplate->module) && isset($customTemplate->name)) {
            $jsonResponse->addTemplate(
                "/bin/template/" . $customTemplate->name,
                $customTemplate->name,
                $customTemplate->module
            );
        } else {
            $jsonResponse->addTemplate(
                "/template/edit/type/" . $this->_item->getType(),
                "klearmatrixEdit"
            );
        }

        $jsonResponse->addTemplateArray(
            $columns->getTypesTemplateArray("/template/field/type/", "klearMatrixFields")
        );
        $jsonResponse->addTemplate(
            $columns->getMultiLangTemplateArray("/template/", 'field'),
            "klearmatrixMultiLangField"
        );
        $jsonResponse->addTemplate("/template/option", "klearmatrixOption");


        $jsonResponse->addJsFile("/js/scripts/2.5.3-crypto-md5.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.autoresize.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.h5validate.js");

        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.edit.js");

        $customScripts = $this->_item->getCustomScripts();
        if (isset($customScripts->module) && isset($customScripts->name)) {
            $jsonResponse->addJsFile("/js/custom/" . $customScripts->name, $customScripts->module);
        }

        $jsonResponse->addJsArray($this->_helper->hookedDataForScreen($this->_item, 'addJsArray', $columns));
        $jsonResponse->addCssArray($this->_helper->hookedDataForScreen($this->_item, 'addCssArray', $columns));
        $jsonResponse->setData($this->_helper->hookedDataForScreen($this->_item, 'setData', $data));
        $jsonResponse->attachView($this->_helper->hookedDataForScreen($this->_item, 'attachView', $this->view));

    }

    protected function _addConditionalBlackList($model)
    {
        $conditionalConfig = $this->_item->getRawConfigAttribute('fields->conditionalBlacklist');

        if ($conditionalConfig) {
            foreach ($conditionalConfig as $field => $fieldConfig) {

                /* Puede que field sea un índice indicativo (en el caso de querer tener
                 * varias condiciones sobre un mismo campo y distintos valores.
                 */
                if (isset($fieldConfig->field)) {
                    $field = $fieldConfig->field;
                }

                $curFieldGetter ='get' .  $model->columnNameToVar($field);
                if ($model->{$curFieldGetter}() == $fieldConfig->condition) {

                    $this->_item->addFieldsToBlackList($fieldConfig->toHideFields);
                }
            }
        }
    }

}
