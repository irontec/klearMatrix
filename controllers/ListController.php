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
        $this->_mainRouter = $this->getRequest()->getUserParam("mainRouter");
        $currentCsv = $this->_mainRouter->getCurrentItem()->getCsvParameters();

        $csvSpec = array(
            'suffix'=>'csv',
            'headers'=>array(
                'Expires'=>0,
                'Cache-control'=>'private',
                'Cache-Control'=>'must-revalidate, post-check=0, pre-check=0',
                'Content-Description'=>'File Transfer',
                'Content-Type'=>'text/csv; charset=utf-8',
                'Content-disposition'=>'attachment; filename='.$currentCsv['filename'].'.csv',
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

        $this->_item = $this->_mainRouter->getCurrentItem();
        $this->_mapperName = $this->_item->getMapperName();
        $this->_mapper = \KlearMatrix_Model_Mapper_Factory::create($this->_mapperName);
        $this->_helper->log('List mapper: ' . $this->_mapperName);

    }


    protected function _getIgnoreBlackList()
    {
        if ($this->getRequest()->getParam("format") == 'csv') {
            $csvParams = $this->_item->getCsvParameters();
            if ($csvParams['ignoreBlackList']) {
                return true;
            }
        }
        return false;
    }

    public function indexAction()
    {
        $data = new KlearMatrix_Model_MatrixResponse();

        $ignoreBlackList = $this->_getIgnoreBlackList();

        $cols = $this->_item->getVisibleColumns($ignoreBlackList);
        $model = $this->_item->getObjectInstance();

        $callerScreen = $this->getRequest()->getPost("callerScreen");
        $parentScreen = $this->_getParentScreen($callerScreen);
        $parentData = $this->_getParentData($parentScreen);

        if (!is_null($parentData)) {
            $parentColumns = $parentScreen->getVisibleColumns();
            $defaultParentCol = $parentColumns->getDefaultCol();

            $getter = 'get' . $parentData->columnNameToVar($defaultParentCol->getDbFieldName());

            $data->setParentIden($parentData->$getter());
            $data->setParentScreen($callerScreen);
            $data->setParentId($parentData->getPrimaryKey());
        }

        $data
            ->setResponseItem($this->_item)
            ->setTitle($this->_item->getTitle())
            ->setColumnCollection($cols)
            ->setPK($this->_item->getPkName())
            ->setResults(array())
            ->setCsv((bool)$this->_item->getCsv());

        $where = $this->_helper->createListWhere($cols, $model, $data, $this->_item, $this->_helper->log);
        $order = $this->_getListOrder($cols);
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

                $data->setFieldOptions($this->_getFieldOptions($cols));
            }

            $data->fixResults($this->_item);
        }

        $data->setInfo($this->_item->getInfo());
        $data->setPreconfiguredFilters($this->_item->getPreconfiguredFilters());
        $data->setGeneralOptions($this->_item->getScreenOptions());
        
        $jsonResponse = KlearMatrix_Model_DispatchResponseFactory::build();
        $jsonResponse->setPlugin($this->_item->getPlugin('list'));

        $jsonResponse->addTemplate("/template/paginator", "klearmatrixPaginator");
        $jsonResponse->addTemplate("/template/list/type/" . $this->_item->getType(), "klearmatrixList");
        $jsonResponse->addTemplate($cols->getMultiLangTemplateArray("/template/", 'list'), "klearmatrixMultiLangList");

        $jsonResponse->addJsFile("/js/plugins/jquery.ui.spinner.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.list.js");

        // Añadimos JS de los campos - tema filtrados -
        $jsonResponse->addJsArray($cols->getColsJsArray());

        $customScripts = $this->_item->getCustomScripts();
        if (isset($customScripts->module) && isset($customScripts->name)) {
            $jsonResponse->addJsFile("/js/custom/" . $customScripts->name, $customScripts->module);
        }

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

    /**
     * Returns parent screen's configuration
     * @return KlearMatrix_Model_Screen|NULL
     */
    protected function _getParentScreen($callerScreen = null)
    {
        if (!$callerScreen || !$this->_item->isFilteredScreen()) {
            return null;
        }

        $parentScreen = new KlearMatrix_Model_Screen;
        $parentScreen->setRouteDispatcher($this->_mainRouter);
        $parentScreen->setConfig($this->_mainRouter->getConfig()->getScreenConfig($callerScreen));

        return $parentScreen;
    }

    /**
     * Returns parent screen's entity object
     * @param KlearMatrix_Model_Screen $parentScreen
     * @return Object Model
     */
    protected function _getParentData(KlearMatrix_Model_Screen $parentScreen = null)
    {
        if (is_null($parentScreen)) {
            return null;
        }

        $parentMapperName = $parentScreen->getMapperName();

        $parentMapper = \KlearMatrix_Model_Mapper_Factory::create($parentMapperName);
        $parentId = $this->_mainRouter->getParam('pk');
        $parentData = $parentMapper->find($parentId);

        return $parentData;
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
    protected function _getListOrder(KlearMatrix_Model_ColumnCollection $cols)
    {
        //Calculamos el orden del listado
        $orderField = $this->getRequest()->getPost("order");
        $orderColumn = $cols->getColFromDbName($orderField);

        if ($orderField && $orderColumn) {
            $this->_helper->log('Order column especified for:' . $this->_mapperName);
            $order = $orderColumn->getOrderField();

            if (! is_array($order)) {
                $order = array($order);
            }

            $orderColumn->setAsOrdered();

            $orderType = 'asc';
            if (in_array($this->getRequest()->getPost("orderType"), array("asc", "desc"))) {

                $orderColumn->setOrderedType($this->getRequest()->getPost("orderType"));
                $orderType = $this->getRequest()->getPost("orderType");
            }

            foreach ($order as $key => $val) {
                $order[$key] .= ' '. $orderType;
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

    protected function _getFieldOptions($cols)
    {
        $defaultOption = $cols->getOptionColumn()->getDefaultOption();
        $fieldOptions = new KlearMatrix_Model_OptionCollection();

        foreach ($this->_item->getScreenFieldsOptionsConfig() as $screen) {

            $screenOption = new KlearMatrix_Model_ScreenOption;
            $screenOption->setName($screen);

            if ($screen === $defaultOption) {

                $screenOption->setAsDefault();
                $defaultOption = false;
            }

            $screenOption->setConfig($this->_mainRouter->getConfig()->getScreenConfig($screen));
            $fieldOptions->addOption($screenOption);
        }

        foreach ($this->_item->getDialogsFieldsOptionsConfig() as $dialog) {

            $dialogOption = new KlearMatrix_Model_DialogOption;
            $dialogOption->setName($dialog);

            if ($dialog === $defaultOption) {

                $dialogOption->setAsDefault();
                $defaultOption = false;
            }

            $dialogOption->setConfig($this->_mainRouter->getConfig()->getDialogConfig($dialog));
            $fieldOptions->addOption($dialogOption);
        }

        return $fieldOptions;
    }

    //Exportamos los resultados a CSV
    public function exportCsv()
    {
        $fields = $this->view->data['columns'];
        $values = $this->_normalizeValues($this->view->data['values']);

        $removePk = true;
        $pkName = $this->_item->getPkName();
        $pkColumn = $this->_item->getVisibleColumns()->getColFromDbName($pkName);
        if ($pkColumn) {
            $removePk = false;
        }

        $csvParams = $this->_item->getCsvParameters();

        $toBeChanged = array();

        foreach ($fields as $field) {
            if ($field['type'] == 'select') {

                $toBeChanged[$field['id']] = array();

                foreach ($field['config']['values'] as $item) {
                    $toBeChanged[$field['id']][$item['key']] = $item['item'];
                }
            }
            $headerstmp[] = $field['name'];
        }

        $fp = fopen("php://temp", "rw");

        if (!is_resource($fp)) {
            throw new Exception('Unable to create output resource for csv.');
        }

        $firstLine = $values[0];

        if ($csvParams['nameklear']) {
            $headers = $headerstmp;
            // Borrar Options
            $options = array_pop($headers);
            unset($headers[$options]);
        } else {
            $headers = array_keys($firstLine);
        }

        if ($removePk) {
            unset($firstLine[$pkName]);
        }

        if ($csvParams['headers']==true) {
            fputcsv($fp, $headers, $csvParams['separator'], $csvParams['enclosure']);
            $this->_fixNewLine($fp, $csvParams['newLine']);
        }

        foreach ($values as $valLine) {

            if ($removePk) {
                unset($valLine[$pkName]);
            }

            foreach ($valLine as $key => $val) {

                if (isset($toBeChanged[$key])) {

                    if (isset($toBeChanged[$key][$val])) {

                        $valLine[$key] = $toBeChanged[$key][$val];
                    } else {

                        $valLine[$key] = '';
                    }
                }
            }

            fputcsv($fp, $valLine, $csvParams['separator'], $csvParams['enclosure']);
            $this->_fixNewLine($fp, $csvParams['newLine']);
        }

        // Read what we have written.
        rewind($fp);
        $strContent = stream_get_contents($fp);

        // Excel SYLK-Bug
        // http://support.microsoft.com/kb/323626/de
        $strContent = preg_replace('/^ID/', 'id', $strContent);


        if (strtoupper($csvParams['encoding']) != 'UTF-8') {
            $strContent = iconv("UTF-8", $csvParams['encoding'], $strContent);
            $intLength = mb_strlen($strContent, $csvParams['encoding']);
        } else {
        //$strContent = utf8_decode($strContent);
            $intLength = mb_strlen($strContent, 'utf-8');
        }

        $this->getResponse()->setHeader('Content-Length', $intLength);
        $this->getResponse()->setHeader('Content-Type', 'text/csv; charset=' . $csvParams['encoding']);

        // Set a header

        // kein fclose($fp);

        $this->getResponse()->setBody($strContent);
    }

    /**
     * Genera las cabeceras y contenidos en multilang
     * @param tmpValues
     * @return array
     */
    protected function _normalizeValues($tmpValues)
    {
        $values = array();
        $valuesSize = count($tmpValues);

        for ($i = 0; $i < $valuesSize; $i++) {

            foreach ($tmpValues[$i] as $fieldName => $value) {

                if (is_array($value)) {

                    foreach ($value as $langKey => $content) {

                        $langs = $fieldName . '_' . $langKey;
                        $values[$i][$langs] = html_entity_decode($content);
                    }
                } else {

                    $values[$i][$fieldName] = html_entity_decode($value);
                }
            }
        }

        return $values;
    }

    protected function _fixNewLine($fp, $newLine)
    {
        if ($newLine === PHP_EOL) {
            //El EOL de PHP es el que se está usando.
            return;
        }

        fseek($fp, mb_strlen(PHP_EOL) * -1, SEEK_CUR);
        fwrite($fp, $newLine);

    }
}