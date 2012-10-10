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

            $setter = $column->getSetterName($model);
            $getter = $column->getGetterName($model);

            if ($column->isMultilang()) {
                $value = array();
                foreach ($columns->getLangs() as $lang) {
                    $value[$lang] = $this->getRequest()->getPost($column->getDbFieldName() . $lang);
                }
            } else {
                $value = $this->getRequest()->getPost($column->getDbFieldName());
            }

            switch(true) {
                case ($column->isMultilang()):
                    foreach ($value as $lang => $_value) {
                        $_value = $column->filterValue($_value, $model->{$getter}($lang));
                        $model->$setter($_value, $lang);
                    }
                    break;

                case ($column->isDependant()):
                    $value = $column->filterValue($value, $model->{$getter}());
                    $model->$setter($value, true);
                    $hasDependant = true;
                    break;

                case ($column->isFile()):
                    $value = $column->filterValue($value, $model->{$getter}());
                    if ($value !== false) {
                        $model->$setter($value['path'], $value['basename']);
                    }
                    break;

                default:
                    $value = $column->filterValue($value, $model->{$getter}());
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

            // TODO: Para el screename del parent, recuperar mapper, fetchById, y comprobar que existe el parámetro recibido.

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
                'message' => 'Registro salvado correctamente.'
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
             ->setColumnWraper($columns);

        // La pantalla "nuevo" tiene filtro? cae de otro listado?
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
        } else {

            $parentData = null;
        }

        /*
         * Es un "new", invocado con PK. Posiblemente desde una opción de campo de una edición.
         * Hay que devolverlo para que se use en la invocación de save
         * y que pueda ser usado como force value con ${param.parentPk}
         */
        $newPk = $this->_mainRouter->getParam('pk', false);
        if (false !== $newPk) {
            $data->setParentPk($newPk);
        }

        $data->setInfo($this->_item->getInfo());
        $data->setGeneralOptions($this->_item->getScreenOptions());
        $data->setActionMessages($this->_item->getActionMessages());

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
        $jsonResponse->setData($this->_getResponseData($data, $parentData));

        //attachView hook
        //TODO: Repasar esto, parece que en la segunda línea faltaría una asignación...
        if ($this->_item->getHook('attachView')) {

            $hook = $this->_item->getHook('attachView');
            $this->_helper->{$hook->helper}->{$hook->action}($this->view);
        }
        $jsonResponse->attachView($this->view);
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
            return $css = $this->_helper->{$hook->helper}->{$hook->action}($columns);
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
}
