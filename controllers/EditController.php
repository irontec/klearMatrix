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
    }

    public function saveAction()
    {
        $mapperName = $this->_item->getMapperName();
        $mapper = \KlearMatrix_Model_Mapper_Factory::create($mapperName);

        $pk = $this->_item->getCurrentPk();

        // TODO: traducir mensaje?
        if (!$model = $mapper->find($pk)) {
            Throw new Zend_Exception('El registro no se encuentra almacenado.');
        }

        $cols = $this->_item->getVisibleColumns();
        $hasDependant = false;

        foreach ($cols as $column) {
            if ($this->_columnIsNotEditable()) {
                continue;
            }

            $setter = $column->getSetterName($model);
            $getter = $column->getGetterName($model);

            if ($column->isMultilang()) {
                $value = array();
                foreach ($cols->getLangs() as $lang) {
                    $value[$lang] = $this->getRequest()->getPost($column->getDbFieldName() . $lang);
                }
            } else {
                $value = $this->getRequest()->getPost($column->getDbFieldName());
            }

            switch(true) {
                case ($column->isMultilang()):
                    foreach ($value as $lang => $_value) {
                        $_value =  $column->filterValue($_value, $model->{$getter}($lang));
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

        try {
            $this->_save($model, $hasDependant);
            $data = array(
                'error' => false,
                'pk' => $model->getPrimaryKey(),
                'message' => 'Registro salvado correctamente.'
            );
        } catch (Zend_Exception $exception) {
            $data = array(
                'error' => true,
                'message'=> $exception->getMessage()
            );
        }

        $jsonResponse = new Klear_Model_SimpleResponse();
        $jsonResponse->setData($data);
        $jsonResponse->attachView($this->view);
    }

    protected function _columnIsNotEditable()
    {
        return $column->isOption() || $column->isReadOnly();
    }

    protected function _save($model, $hasDependant)
    {
        if (method_exists($model, 'saveRecursive')) {
            if ($hasDependant) {
                $pk = $model->saveRecursive();
            } else {
                $pk = $model->save();
            }
        } else {
            $pk = $model->save(false, $hasDependant);
        }

        if (!$pk) {
            throw new \Zend_Exception('Error salvando el registro');
        }
    }

    public function indexAction()
    {
        $mapperName = $this->_item->getMapperName();
        $mapper = \KlearMatrix_Model_Mapper_Factory::create($mapperName);

        $pk = $this->_item->getCurrentPk();

        $data = new KlearMatrix_Model_MatrixResponse;
        $cols = $this->_item->getVisibleColumns();

        $data
            ->setTitle($this->_item->getTitle())
            ->setColumnWraper($cols)
            ->setPK($this->_item->getPkName())
            ->setResponseItem($this->_item);

        if (!$model = $mapper->find($pk)) {

            exit;// Error

        } else {

            $data->setResults($model)
                 ->fixResults($this->_item);
        }

        //TODO: Fix!! c/p from NewController >> Quiero devolver los datos de su padre, para las opciones de columna
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

        $data->setInfo($this->_item->getInfo());
        $data->setGeneralOptions($this->_item->getScreenOptions());
        $data->setActionMessages($this->_item->getActionMessages());
        $data->setDisableSave($this->_item->getDisableSave());

        Zend_Json::$useBuiltinEncoderDecoder = true;

        $jsonResponse = new Klear_Model_DispatchResponse();
        $jsonResponse->setModule('klearMatrix');
        $jsonResponse->setPlugin($this->_item->getPlugin('edit'));

        $customTemplate = $this->_item->getCustomTemplate();
        if (isset($customTemplate->module) && isset($customTemplate->name)) {
            $jsonResponse->addTemplate(
                "/bin/template/" . $customTemplate->name, $customTemplate->name,
                $customTemplate->module
            );
        } else {
            $jsonResponse->addTemplate(
                "/template/edit/type/" . $this->_item->getType(),
                "klearmatrixEdit"
            );
        }

        $jsonResponse->addTemplateArray(
            $cols->getTypesTemplateArray("/template/field/type/", "klearMatrixFields")
        );
        $jsonResponse->addTemplate(
            $cols->getMultiLangTemplateArray("/template/", 'field'),
            "klearmatrixMultiLangField"
        );

        $jsonResponse->addJsFile("/js/plugins/jquery.h5validate.js");

        $jsonResponse->addJsFile("/js/plugins/jquery.autoresize.js");
        $jsonResponse->addJsFile("/js/scripts/2.5.3-crypto-md5.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.ui.form.js");

        //addJsArray hook
        if ($this->_item->getHook('addJsArray')) {

            $hook = $this->_item->getHook('addJsArray');
            $js = $this->_helper->{$hook->helper}->{$hook->action}($cols);

        } else {

            $js = $cols->getColsJsArray();
        }

        $jsonResponse->addJsArray($js);

        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.template.helper.js");
        $jsonResponse->addJsFile("/js/translation/jquery.klearmatrix.translation.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.module.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.edit.js");

        $customScripts = $this->_item->getCustomScripts();
        if (isset($customScripts->module) and isset($customScripts->name)) {
            $jsonResponse->addJsFile("/js/custom/" . $customScripts->name, $customScripts->module);
        }

        $jsonResponse->addCssFile("/css/klearMatrix.css");

        //addCssArray hook
        if ($this->_item->getHook('addCssArray')) {

            $hook = $this->_item->getHook('addCssArray');
            $css = $this->_helper->{$hook->helper}->{$hook->action}($cols);

        } else {

            $css = $cols->getColsCssArray();
        }

        $jsonResponse->addCssArray($css);

        //setData hook
        if ($this->_item->getHook('setData')) {

            $hook = $this->_item->getHook('setData');
            $data = $this->_helper->{$hook->helper}->{$hook->action}($data, $parentData);

        } else {

            $data = $data->toArray();
        }

        $jsonResponse->setData($data);

        //attachView hook
        if ($this->_item->getHook('attachView')) {

            $hook = $this->_item->getHook('attachView');
            $this->_helper->{$hook->helper}->{$hook->action}($this->view);
        }

        $jsonResponse->attachView($this->view);
    }
}
