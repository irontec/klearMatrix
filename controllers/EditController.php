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
            Throw new Zend_Exception($this->view->translate('El registro no se encuentra almacenado.'));
        }

        $columns = $this->_item->getVisibleColumns();
        $hasDependant = false;

        foreach ($columns as $column) {
            if ($this->_columnIsNotEditable($column)) {
                continue;
            }

            $setter = $column->getSetterName();

            if ($column->isMultilang()) {
                $value = array();
                foreach ($columns->getLangs() as $lang) {
                    $value[$lang] = $this->getRequest()->getPost($column->getDbFieldName() . $lang);
                }
            } else {
                $value = $this->getRequest()->getPost($column->getDbFieldName());
            }

            // Avoid accidental DB data deletion. If we don't get the POST param, we don't touch the field
            if (is_null($value)) {
                continue;
            }

            $value = $column->filterValue($value);

            switch(true) {
                case ($column->isMultilang()):
                    foreach ($value as $lang => $_value) {
                        $model->$setter($_value, $lang);
                    }
                    break;

                case ($column->isDependant()):
                    $model->$setter($value, true);
                    $hasDependant = true;
                    break;

                case ($column->isFile()):
                    if ($value !== false && file_exists($value['path'])) {

                        $model->$setter($value['path'], $value['basename']);
                    }
                    break;

                default:
                    $model->$setter($value);
            }
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

        //TODO: Fix!! c/p from NewController >> Quiero devolver los datos de su padre, para las opciones de columna
        $parentData = null;

        if ($this->_item->isFilteredScreen()) {

            // Informamos a la respuesta de que campo es el "padre"
            $data->setParentItem($this->_item->getFilterField());

            // A partir del nombre de pantalla (de nuestro .yaml principal...
            if ($parentScreenName = $this->getRequest()->getPost("parentScreen")) {

                // Instanciamos pantalla
                $parentScreen = new KlearMatrix_Model_Screen;
                $parentScreen->setRouteDispatcher($this->_mainRouter);
                $parentScreen->setConfig($this->_mainRouter->getConfig()->getScreenConfig($parentScreenName));
                $parentMapperName = $parentScreen->getMapperName();

                $parentColumns = $parentScreen->getVisibleColumns();
                $defaultParentCol = $parentColumns->getDefaultCol();

                // Recuperamos mapper, para recuperar datos principales (default value)
                $parentMapper = \KlearMatrix_Model_Mapper_Factory::create($parentMapperName);
                $parentId = $this->_mainRouter->getParam('parentId');
                $parentData = $parentMapper->find($parentId);

                $getter = 'get' . $parentData->columnNameToVar($defaultParentCol->getDbFieldName());

                // Se añaden los datos a la respuesta
                // Se recogerán en el new, y se mostrará información por pantalla
                $data->setParentIden($parentData->$getter());
                $data->setParentId($parentId);
                $data->setParentScreen($parentScreenName);
            }
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
        $jsonResponse->setData($this->_getResponseData($data, $parentData));
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

    protected function _getResponseData($data, $parentData = null)
    {
        if ($this->_item->getHook('setData')) {

            $hook = $this->_item->getHook('setData');
            return $this->_helper->{$hook->helper}->{$hook->action}($data, $parentData);
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
