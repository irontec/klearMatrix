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

    protected function _retrieveValueForColumn($column, $langs)
    {
        $request = $this->getRequest();

        if (!$column->isMultilang()) {
            return $request->getPost($column->getDbFieldName());
        }

        $value = array();
        foreach ($langs as $lang) {
            $value[$lang] = $request->getPost($column->getDbFieldName() . $lang);
        }
        return $value;
    }

    /**
     * @param unknown $model Entidad sobre la que se setea
     * @param unknown $column Campo concreto que se comprueba
     */
    protected function _parseColumnIntoModel($model, $column)
    {
        if ($this->_columnIsNotEditable($column)) {
            return;
        }

        $setter = $column->getSetterName();

        $value = $this->_retrieveValueForColumn($column, $model->getAvailableLangs());

        // Avoid accidental DB data deletion. If we don't get the POST param, we don't touch the field
        if (is_null($value)) {
            return;
        }

        $value = $column->filterValue($value);

        if ($column->isMultilang()) {
            foreach ($value as $lang => $_value) {
                $model->$setter($_value, $lang);
            }
            return;
        }

        if ($column->isDependant()) {
            $model->$setter($value, true);
            return;
        }

        if ($column->isFile()) {
            if ($value !== false && file_exists($value['path'])) {
                $model->$setter($value['path'], $value['basename']);
            }
            return;
        }

        $model->$setter($value);
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
            Throw new Zend_Exception($this->view->translate('El registro no se encuentra almacenado.'));
        }

        $columns = $this->_item->getVisibleColumns();
        $hasDependant = false;

        foreach ($columns as $column) {
            $this->_parseColumnIntoModel($model, $column);

            // Si una de las columnas tienen dependencias,
            // el save deberá llevar "saveRecursive"
            $hasDependant |= $column->isDependant();
        }

        try {
            $this->_save($model, $hasDependant);
            $this->_helper->log(
                'model save succesfully for ' . $mapperName . ' > PK('.$pk.')'
            );
            $data = array(
                'error' => false,
                'pk' => $model->getPrimaryKey(),
                'message' => $this->view->translate('Registro salvado correctamente.')
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

    protected function _columnIsNotEditable(KlearMatrix_Model_Column $column)
    {
        return $column->isOption() || $column->isReadOnly();
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
                'Error salvando el registro: ' . $exception->getMessage()
            );
            throw new \Zend_Exception($this->view->translate('Error salvando el registro'));
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

        $columns = $this->_item->getVisibleColumns();

        $data = new KlearMatrix_Model_MatrixResponse;

        $data
            ->setTitle($this->_item->getTitle())
            ->setColumnCollection($columns)
            ->setPK($this->_item->getPkName())
            ->setResponseItem($this->_item);

        $data->setResults($model)
             ->fixResults($this->_item);

        if ($this->_item->isFilteredScreen()) {
            $parentScreenName = $this->getRequest()->getPost("parentScreen", false);
            $data->calculateParentData($this->_mainRouter, $parentScreenName);
        }

        $data->setInfo($this->_item->getInfo());
        $data->setGeneralOptions($this->_item->getScreenOptions());
        $data->setActionMessages($this->_item->getActionMessages());
        $data->setDisableSave($this->_item->getDisableSave());

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

        $jsonResponse->addJsFile("/js/scripts/2.5.3-crypto-md5.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.autoresize.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.h5validate.js");

        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.edit.js");

        $customScripts = $this->_item->getCustomScripts();
        if (isset($customScripts->module) && isset($customScripts->name)) {
            $jsonResponse->addJsFile("/js/custom/" . $customScripts->name, $customScripts->module);
        }

        // Get data from hooks (if any)
        $jsonResponse->addJsArray($this->_getJsArray($columns));
        $jsonResponse->addCssArray($this->_getCssArray($columns));
        $jsonResponse->setData($this->_getResponseData($data));
        $jsonResponse->attachView($this->_getView());
    }

    protected function _addConditionalBlackList($model)
    {
        $conditionalConfig = $this->_item->getRawConfigAttribute('fields->conditionalBlacklist');

        if ($conditionalConfig) {
            foreach ($conditionalConfig as $field => $fieldConfig) {

                $curFieldGetter ='get' .  $model->columnNameToVar($field);
                if ($model->{$curFieldGetter}() == $fieldConfig->condition) {

                    $this->_item->addFieldsToBlackList($fieldConfig->toHideFields);
                }
            }
        }
    }

    protected function _getJsArray(KlearMatrix_Model_ColumnCollection $columns)
    {
        //addJsArray hook
        if ($this->_item->getHook('addJsArray')) {

            $hook = $this->_item->getHook('addJsArray');
            return $this->_helper->{$hook->helper}->{$hook->action}($columns);
        }

        return $columns->getColsJsArray();
    }

    protected function _getCssArray(KlearMatrix_Model_ColumnCollection $columns)
    {
        if ($this->_item->getHook('addCssArray')) {

            $hook = $this->_item->getHook('addCssArray');
            return $this->_helper->{$hook->helper}->{$hook->action}($columns);
        }

        return $columns->getColsCssArray();
    }

    protected function _getResponseData($data)
    {
        if ($this->_item->getHook('setData')) {

            $hook = $this->_item->getHook('setData');
            return $this->_helper->{$hook->helper}->{$hook->action}($data, $data->getParentData());
        }

        return $data->toArray();
    }

    protected function _getView()
    {
        if ($this->_item->getHook('attachView')) {

            $hook = $this->_item->getHook('attachView');
            return $this->_helper->{$hook->helper}->{$hook->action}($this->view);
        }

        return $this->view;
    }
}
