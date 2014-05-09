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

        foreach ($columns as $column) {
            $this->_helper->Column2Model($model, $column);

            // Si una de las columnas tienen dependencias,
            // el save deberá llevar "saveRecursive"
            $hasDependant |= $column->isDependant();
        }

        try {

            $this->_save($model, $hasDependant);
            $this->_helper->log(
                'model created succesfully for ' . $mapperName
            );

            $optsString = "";
            if ($this->_item->hasEntityPostSaveOptions()) {
                $fieldOpts = $this->_getFieldOptions();
                foreach($fieldOpts as $opt) {
                    $optsString.= "<span data-id='".$model->getPrimaryKey()."'>".$opt->toAutoOption()."</span>";
                }
            }

            //TODO mejoprar viusazlcion de otsastrn
            $data = array(
                'error' => false,
                'pk' => $model->getPrimaryKey(),
                'message' => $this->view->translate('Record successfully saved.') . $optsString
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

    protected function _getFieldOptions()
    {

        $fieldOptions = new KlearMatrix_Model_OptionCollection();

        foreach ($this->_item->getScreenEntityPostSaveOptionsConfig() as $screen) {

            $screenOption = new KlearMatrix_Model_ScreenOption;
            $screenOption->setName($screen);
            $screenOption->setConfig($this->_mainRouter->getConfig()->getScreenConfig($screen));
            $screenOption->setFrom("entityPostSaveDialog");
            $screenOption->setParentHolderSelector('span');
            $fieldOptions->addOption($screenOption);
        }

        return $fieldOptions;
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
            throw new \Zend_Exception('Error saving record ('.$exception->getMessage().')');
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


        $parentScreenName = $this->getRequest()->getPost("parentScreen", false);
        if (false !== $parentScreenName) {
            $data->calculateParentData($this->_mainRouter, $parentScreenName, NULL);
        } else {
            $parentScreenName = $this->getRequest()->getPost("callerScreen", false);
            if (false !== $parentScreenName && $this->getRequest()->getParam("pk")) {
                $data->calculateParentData($this->_mainRouter, $parentScreenName, $this->getRequest()->getParam("pk"));
            }
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

        $jsonResponse->addJsArray($this->_helper->hookedDataForScreen($this->_item, 'addJsArray', $columns));
        $jsonResponse->addCssArray($this->_helper->hookedDataForScreen($this->_item, 'addCssArray', $columns));
        $jsonResponse->setData($this->_helper->hookedDataForScreen($this->_item, 'setData', $data));
        $jsonResponse->attachView($this->_helper->hookedDataForScreen($this->_item, 'attachView', $this->view));

    }

}
