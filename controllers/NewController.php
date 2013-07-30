<?php

class KlearMatrix_NewController extends Zend_Controller_Action
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

        $model = $this->_item->getObjectInstance();
        // Cargamos las columnas visibles, ignorando blacklist

        $this->_helper->log('new::save action for mapper:' . $mapperName);

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
                    if ($value !== false) {
                        $model->$setter($value['path'], $value['basename']);
                    }
                    break;

                default:
                    $model->$setter($value);
            }
        }

        if ($this->_item->hasForcedValues()) {
            foreach ($this->_item->getForcedValues() as $field => $value) {
                try {
                    $varName = $model->columnNameToVar($field);
                    $model->{'set' . $varName}($value);
                } catch (Exception $e) {
                    // Nothing to do... condition not found in model... :S
                    // Debemos morir??
                }
            }
        }

        // Si la pantalla esta filtrada, debemos setearla en la "nueva"
        if ($this->_item->isFilteredScreen()) {


            $filteredField = $this->_item->getFilterField();

            //TODO: Desgaretizar parentPk / parentId :S en todos los controllers --- muy complicado.
            $parentPk = $this->_mainRouter->getParam("parentPk", false);
            if ($parentPk) {
                //pantalla new desde un edit
                $filteredValue = $parentPk;
            } else {
                $filteredValue = $this->_mainRouter->getParam($filteredField);
            }

            // TODO: Para el screename del parent,
            // recuperar mapper. Hacer fetchById y comprobar
            // que existe el parámetro recibido.

            $filterFieldSetter = 'set' . $model->columnNameToVar($filteredField);
            $model->{$filterFieldSetter}($filteredValue);
        }

        try {
            $this->_save($model, $hasDependant);
            $this->_helper->log(
                'model created succesfully for ' . $mapperName
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
                'Error saving in new::save for ' . $mapperName . ' ['.$exception->getMessage().']',
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
            throw new \Zend_Exception('Error salvando el registro');
        }
    }

    public function indexAction()
    {
        $this->_helper->log('New for mapper:' . $this->_item->getMapperName());

        $columns = $this->_item->getVisibleColumns();

        $data = new KlearMatrix_Model_MatrixResponse;

        $data->setResponseItem($this->_item)
             ->setTitle($this->_item->getTitle())
             ->setColumnCollection($columns);


        // La pantalla "nuevo" tiene filtro?  >> cae de otro listado.
        if ($this->_item->isFilteredScreen()) {
            $parentScreenName = $this->getRequest()->getPost("parentScreen", false);
            $data->calculateParentData($this->_mainRouter, $parentScreenName);
        }

        $data->parseItemAttrs($this->_item);

        $jsonResponse = KlearMatrix_Model_DispatchResponseFactory::build();
        $jsonResponse->setPlugin($this->_item->getPlugin('klearMatrixNew'));

        $customTemplate = $this->_item->getCustomTemplate();

        if (isset($customTemplate->module) && isset($customTemplate->name)) {
            $jsonResponse->addTemplate(
                "/bin/template/" . $customTemplate->name,
                $customTemplate->name,
                $customTemplate->module
            );
        } else {
            $jsonResponse->addTemplate(
                "/template/new/type/" . $this->_item->getType(),
                "klearmatrixNew"
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

        // klearmatrix.new hereda de klearmatrix.edit
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.edit.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.new.js");

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
