<?php

class KlearMatrix_ListController extends Zend_Controller_Action
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

    protected $_mapperName;
    protected $_mapper;


    public function init()
    {
        /* Initialize action controller here */
        $this->_helper->layout->disableLayout();

        $csvSpec = array(
            'suffix'=>'csv',
            'headers'=>array(
                'Expires'=>0,
                'Cache-control'=>'private',
                'Cache-Control'=>'must-revalidate, post-check=0, pre-check=0',
                'Content-Description'=>'File Transfer',
                'Content-Type'=>'text/csv; charset=utf-8',
                'Content-disposition'=>'attachment; filename=export.csv',
            ),
            'callbacks'=>array(
                'init' => 'initJsonContext',
                'post' => array($this, 'exportCsv')
            )
        );

        $context = $this->_helper->ContextSwitch();
        $context
            ->addContext('csv', $csvSpec)
            ->setAutoDisableLayout(true)
            ->setDefaultContext('json')
            ->addActionContext('index', array('json', 'csv'));

        $contextParam = $this->getRequest()->getParam($context->getContextParam());

        if (empty($contextParam)) {
            $context
                ->initContext($context->getDefaultContext());
        } else {
            $context
                ->initContext($contextParam);
        }

        $this->_mainRouter = $this->getRequest()->getUserParam("mainRouter");
        $this->_item = $this->_mainRouter->getCurrentItem();
        $this->_mapperName = $this->_item->getMapperName();
        $this->_mapper = \KlearMatrix_Model_Mapper_Factory::create($this->_mapperName);
        $this->_helper->log('List mapper: ' . $this->_mapperName);
    }


    public function indexAction()
    {
        $data = new KlearMatrix_Model_MatrixResponse();

        $cols = $this->_item->getVisibleColumns();
        $model = $this->_item->getObjectInstance();

        if ($this->_item->isFilteredScreen()) {

            $callerScreen = $this->getRequest()->getPost("callerScreen");
            if ($callerScreen) {

               $parentScreen = new KlearMatrix_Model_Screen;
               $parentScreen->setRouteDispatcher($this->_mainRouter);
               $parentScreen->setConfig($this->_mainRouter->getConfig()->getScreenConfig($callerScreen));
               $parentMapperName = $parentScreen->getMapperName();

               $parentColumns = $parentScreen->getVisibleColumns();
               $defaultParentCol = $parentColumns->getDefaultCol();

               $parentMapper = \KlearMatrix_Model_Mapper_Factory::create($parentMapperName);
               $parentId = $this->_mainRouter->getParam('pk');
               $parentData = $parentMapper->find($parentId);

               $getter = 'get' . $parentData->columnNameToVar($defaultParentCol->getDbFieldName());

               $data->setParentIden($parentData->$getter());
               $data->setParentScreen($callerScreen);
               $data->setParentId($parentId);
            }
        } else {

            $parentData = null;
        }

        $data
            ->setResponseItem($this->_item)
            ->setTitle($this->_item->getTitle())
            ->setColumnWraper($cols)
            ->setPK($this->_item->getPkName())
            ->setResults(array())
            ->setCsv((bool)$this->_item->getCsv());

        $where = $this->_getWhere($cols, $model, $data);
        $order = $this->_getListOrder($cols, $model);
        $count = $this->_getItemsPerPage();
        $page = $this->_getCurrentPage();
        $offset = $this->_getOffset($count, $page);

        $results = $this->_mapper->fetchList($where, $order, $count, $offset);
        $this->_helper->log(sizeof($results) . ' elements return by fetchList for:' . $this->_mapperName);

        if (is_array($results)) {

            $totalItems = $this->_mapper->countByQuery($where);

            if (!is_null($count) && !is_null($offset)) {

                $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Null($totalItems));

                $paginator->setCurrentPageNumber($page);
                $paginator->setItemCountPerPage($count);

                $data->setPaginator($paginator);


            }

            $data->setTotal($totalItems);
            $data->setResults($results);

            if ($this->_item->hasFieldOptions()) {

                $defaultOption = $cols->getOptionColumn()->getDefaultOption();
                $fieldOptions = new KlearMatrix_Model_OptionCollection();

                foreach ($this->_item->getScreenFieldsOptionsConfig() as $_screen) {

                    $screenOption = new KlearMatrix_Model_ScreenOption;
                    $screenOption->setName($_screen);

                    if ($_screen === $defaultOption) {

                        $screenOption->setAsDefault();
                        $defaultOption = false;
                    }

                    // Recuperamos la configuración del screen, de la configuración general del módulo
                    // Supongo que cuando lo vea Alayn, le gustará mucho :)
                        // Lo he visto y solo digo: http://en.wikipedia.org/wiki/Law_of_Demeter :p

                    // El "nombre" mainRouter apesta... pero... O:)
                        // Pero habría que cambiarlo, no?

                    $screenOption->setConfig($this->_mainRouter->getConfig()->getScreenConfig($_screen));
                    $fieldOptions->addOption($screenOption);
                }

                foreach ($this->_item->getDialogsFieldsOptionsConfig() as $_dialog) {

                    $dialogOption = new KlearMatrix_Model_DialogOption;
                    $dialogOption->setName($_dialog);

                    if ($_dialog === $defaultOption) {

                        $dialogOption->setAsDefault();
                        $defaultOption = false;
                    }

                    $dialogOption->setConfig($this->_mainRouter->getConfig()->getDialogConfig($_dialog));
                    $fieldOptions->addOption($dialogOption);
                }

                $data->setFieldOptions($fieldOptions);
            }

            $data->fixResults($this->_item);
        }

        $data->setInfo($this->_item->getInfo());
        $data->setGeneralOptions($this->_item->getScreenOptions());

        Zend_Json::$useBuiltinEncoderDecoder = true;

        $jsonResponse = new Klear_Model_DispatchResponse;
        $jsonResponse->setModule('klearMatrix');
        $jsonResponse->setPlugin($this->_item->getPlugin('list'));

        $jsonResponse->addTemplate("/template/paginator", "klearmatrixPaginator");
        $jsonResponse->addTemplate("/template/list/type/" . $this->_item->getType(), "klearmatrixList");
        $jsonResponse->addTemplate($cols->getMultiLangTemplateArray("/template/", 'list'), "klearmatrixMultiLangList");
        $jsonResponse->addJsFile("/js/plugins/jquery.ui.form.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.ui.spinner.js");

        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.template.helper.js");
        $jsonResponse->addJsFile("/js/translation/jquery.klearmatrix.translation.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.module.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.list.js");

        // Añadimos JS de los campos - tema filtrados -
        $jsonResponse->addJsArray($cols->getColsJsArray());

        $customScripts = $this->_item->getCustomScripts();
        if (isset($customScripts->module) and isset($customScripts->name)) {
            $jsonResponse->addJsFile("/js/custom/" . $customScripts->name, $customScripts->module);
        }

        $jsonResponse->addCssFile("/css/klearMatrix.css");
        $jsonResponse->addCssFile("/css/jquery.ui.spinner.css");
        //setData hook

        $hook = $this->_item->getHook('setData');
        if ($hook) {

            $data = $this->_helper->{$hook->helper}->{$hook->action}($data, $parentData);

        } else {

            $data = $data->toArray();
        }

        $jsonResponse->setData($data);

        //attachView hook
        $hook = $this->_item->getHook('attachView');
        if ($hook) {

            $this->_helper->{$hook->helper}->{$hook->action}($this->view);
        }

        $jsonResponse->attachView($this->view);
    }

    protected function _getWhere(KlearMatrix_Model_ColumnCollection $cols, $model, KlearMatrix_Model_MatrixResponse $data)
    {
        $where = array();

        if ($this->_item->hasFilterClass()) {
            $where[] = $this->_item->getFilterClassCondition();
        }

        if ($this->_item->isFilteredScreen()) {
            $where[] = $this->_item->getFilteredCondition($this->_mainRouter->getParam('pk'));
        }

        if ($this->_item->hasForcedValues()) {
            $where = array_merge($where, $this->_item->getForcedValuesConditions());
        }

        //Generamos el where de los filtros
        $searchFields = $this->getRequest()->getPost("searchFields");
        $searchOps = $this->getRequest()->getPost("searchOps");

        if ($searchFields) {

            $this->_helper->log('Search arguments found for:' . $this->_mapperName);

            $searchWhere = array();

            foreach ($searchFields as $field => $values) {

                $valuesOp = $searchOps[$field];
                $col = $cols->getColFromDbName($field);
                if ($col) {

                    $searchWhere[] = $col->getSearchCondition($values, $valuesOp, $model, $cols->getLangs());
                    $data->addSearchField($field, $values, $valuesOp);
                }
            }

            $expressions = $values = array();

            foreach ($searchWhere as $condition) {

                if (is_array($condition)) {

                    $expressions[] = $condition[0];
                    $values = array_merge($values, $condition[1]);

                } else {

                    $expressions[] = $condition;
                }
            }

            if ($this->getRequest()->getPost("searchAddModifier") == '1') {

                $data->addSearchAddModifier(true);
                $where[] = array('(' . implode(" or ", $expressions) . ')', $values);

            } else {

                $where[] = array('(' . implode(" and ", $expressions) . ')', $values);
            }
        }

        if (count($where) == 0) {

            $where = null;

        } else {

            $values = $expressions = array();

            foreach ($where as $condition) {

                $expressions[] = $condition[0];
                $values = array_merge($values, $condition[1]);
            }

            $where = array(implode(" and ", $expressions), $values);
        }

        return $where;
    }

    protected function _getItemsPerPage()
    {
        //Calculamos la página en la que estamos y el offset
        $paginationConfig = $this->_item->getPaginationConfig();
        if (
            ($paginationConfig instanceof Klear_Model_ConfigParser)
            && ($this->_helper->ContextSwitch()->getCurrentContext() != 'csv')
        ) {

            $count = $paginationConfig->getproperty('items');
            $currentCount = (int)$this->getRequest()->getPost("count");

            if ($currentCount) {

                $count = $currentCount;
            }

            return $count;
        }
        return null;
    }

    protected function _getOffset($itemsPerPage, $page)
    {

        if ($itemsPerPage) {
            return $itemsPerPage * ($page - 1);
        }
        return null;
    }

    protected function _getCurrentPage()
    {
        $page = 1;
        $currentPage = (int)$this->getRequest()->getPost("page");

        if ($currentPage > 0) {
            $page = $currentPage;
        }

        return $page;
    }

    /**
     * Returns order query part
     * @param KlearMatrix_Model_ColumnCollection $cols
     * @param Object $model
     * @return string
     */
    protected function _getListOrder(KlearMatrix_Model_ColumnCollection $cols, $model)
    {
        //Calculamos el orden del listado
        $orderField = $this->getRequest()->getPost("order");
        $orderColumn = $cols->getColFromDbName($orderField);

        if ($orderField && $orderColumn) {
            $this->_helper->log('Order column especified for:' . $this->_mapperName);
            $order = $orderColumn->getOrderField($model);

            $orderColumn->setAsOrdered();

            if (in_array($this->getRequest()->getPost("orderType"), array("asc", "desc"))) {

                $orderColumn->setOrderedType($this->getRequest()->getPost("orderType"));
                $order .= ' ' . $this->getRequest()->getPost("orderType");

            } else {

                $order .= ' asc';
            }

        } else {

            $orderConfig = $this->_item->getOrderConfig();

            if ($orderConfig && $orderConfig->getProperty('field')) {

                $order = $orderConfig->getProperty('field');

                if ($order instanceof Zend_Config) {

                    $order = $order->toArray();
                }

                if (!is_array($order)) {

                    $order = array($order);
                }

                if ($orderConfig->getProperty('type')) {

                    foreach ($order as $key => $val) {

                        $order[$key] .= ' '. $orderConfig->getProperty('type');
                    }
                }

            } else {

                // Por defecto ordenamos por PK
                $order = $this->_item->getPkName();
            }
        }
        return $order;
    }

    //Exportamos los resultados a CSV
    public function exportCsv()
    {
        $fields = $this->view->data['columns'];
        $values = $this->view->data['values'];

        $toBeChanged = array();

        foreach ($fields as $field) {

            if ($field['type'] == 'select') {

                $toBeChanged[$field['id']] = array();

                foreach ($field['config']['values'] as $item) {
                    $toBeChanged[$field['id']][$item['key']] = $item['item'];
                }
            }
        }

        ob_start();
        $fp = fopen("php://output", "w");

        if (is_resource($fp)) {

            foreach ($values as $valLine) {

                foreach ($valLine as $key => $val) {

                    if (isset($toBeChanged[$key])) {

                        if (isset($toBeChanged[$key][$val])) {

                            $valLine[$key] = $toBeChanged[$key][$val];
                        } else {

                            $valLine[$key] = '';
                        }
                    }
                }

                fputcsv($fp, $valLine, ';', '"');
            }

            $strContent = ob_get_clean();

            // Excel SYLK-Bug
            // http://support.microsoft.com/kb/323626/de
            $strContent = preg_replace('/^ID/', 'id', $strContent);

            //$strContent = utf8_decode($strContent);
            $intLength = mb_strlen($strContent, 'utf-8');

            // length
            $this->getResponse()->setHeader('Content-Length', $intLength);
            // Set a header

            // kein fclose($fp);

            $this->getResponse()->setBody($strContent);

        } else {
            ob_end_clean();
            Throw new Exception('Unable to create output resource for csv.');
        }
    }
}
