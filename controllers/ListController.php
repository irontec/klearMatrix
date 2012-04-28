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


    public function init()
    {
        /* Initialize action controller here */
        $this->_helper->layout->disableLayout();

        $this->_helper->ContextSwitch()
            ->addActionContext('index', 'json')
            ->initContext('json');

        $this->_mainRouter = $this->getRequest()->getParam("mainRouter");
        $this->_item = $this->_mainRouter->getCurrentItem();
    }


    public function indexAction()
    {

        $mapperName = $this->_item->getMapperName();
        $mapper = new $mapperName;

        $data = new KlearMatrix_Model_MatrixResponse;
        $cols = $this->_item->getVisibleColumnWrapper();

        $model = $this->_item->getObjectInstance();

        $where = array();


        if ($this->_item->isFilteredScreen()) {

            $where[] = $this->_item->getFilteredCondition($this->_mainRouter->getParam('pk'));

            if ($callerScreen = $this->getRequest()->getPost("callerScreen")) {

               $parentScreen = new KlearMatrix_Model_Screen;
               $parentScreen->setRouteDispatcher($this->_mainRouter);
               $parentScreen->setConfig($this->_mainRouter->getConfig()->getScreenConfig($callerScreen));
               $parentMapperName = $parentScreen->getMapperName();

               $parentColWrapper = $parentScreen->getVisibleColumnWrapper();
               $defaultParentCol = $parentColWrapper->getDefaultCol();

               $parentMapper = new $parentMapperName;
               $parentId = $this->_mainRouter->getParam('pk');
               $parentData = $parentMapper->find($parentId);

               $getter = 'get' . $parentData->columnNameToVar($defaultParentCol->getDbName() );
               $data->setParentIden($parentData->$getter());
               $data->setParentScreen($callerScreen);
               $data->setParentId($parentId);

            }
        }

        if ($this->_item->hasForcedValues()) {
            $where = array_merge($where,$this->_item->getForcedValuesConditions());
        }

        if ($searchFields = $this->getRequest()->getPost("searchFields")) {
            $_searchWhere = array();

            foreach ($searchFields as $field => $values) {
                if ($col = $cols->getColFromDbName($field)) {
                    $_searchWhere[] = $col->getSearchCondition($values,$model,$cols->getLangs());
                    $data->addSearchField($field,$values);
                }
            }

            $expresions = $values = array();
            foreach ($_searchWhere as $condition) {
                $expresions[] = $condition[0];
                $values = array_merge($values,$condition[1]);
            }

            if ($this->getRequest()->getPost("searchAddModifier") == '1') {
                $data->addSearchAddModifier(true);
                $where[] = array(implode ( " or ", $expresions),$values);

            } else {

                $where[] = array(implode ( " and ", $expresions),$values);
            }
        }

        if ( ($orderField = $this->getRequest()->getPost("order")) && ($orderColumn = $cols->getColFromDbName($orderField)) ) {

            $order = $orderColumn->getOrderField($model);

            $orderColumn->setAsOrdered();

            if (in_array($this->getRequest()->getPost("orderType"),array("asc","desc")) ){

                $orderColumn->setOrderedType($this->getRequest()->getPost("orderType"));
                $order .= ' ' . $this->getRequest()->getPost("orderType");

            } else {
                $order .= ' asc';
            }

        } else {
            if ( ($orderConfig = $this->_item->getOrderConfig()) &&
                ($orderConfig->getProperty('field')) ) {
                    $order = $orderConfig->getProperty('field');
                    if ($orderConfig->getProperty('type')) {
                        $order .= ' '. $orderConfig->getProperty('type');
                    }

            } else {
                $order = $this->_item->getPK(); // Por defecto ordenamos por PK
            }
        }


        if ($paginationConfig = $this->_item->getPaginationConfig()) {

            $configCount = $paginationConfig->getproperty('items');

            if ($currentCount = (int)$this->getRequest()->getPost("count")) {
                $count = $currentCount;
            } else {
                $count = $configCount;
            }

            if ($currentPage = (int)$this->getRequest()->getPost("page")) {
                $page = ($currentPage<1)? 1:$currentPage;
            } else {
                $page = 1;
            }

            $offset = ($page-1)*$count;


        } else {
            $count = NULL;
            $offset = NULL;
        }



        $data
            ->setResponseItem($this->_item)
            ->setTitle($this->_item->getTitle())
            ->setColumnWraper($cols)
            ->setPK($this->_item->getPK());

        if (sizeof($where) == 0) {

            $where = null;

        } else {

            $values = $expresions = array();
            foreach ($where as $condition) {
                $expresions[] = $condition[0];
                $values = array_merge($values,$condition[1]);
            }

            $where = array(implode ( " and ", $expresions),$values);


        }

        if (!$results= $mapper->fetchList($where, $order, $count, $offset)) {
            // No hay resultados
            $data->setResults(array());

        } else {

            if (!is_null($count) && !is_null($offset) ) {

                $totalItems = $mapper->countByQuery($where);
                $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Null($totalItems));
                $paginator->setCurrentPageNumber($page);
                $paginator->setItemCountPerPage($count);

                $data->setPaginator($paginator);
            }

             $data->setResults($results);

            if ($this->_item->hasFieldOptions()) {

                $defaultOption = $cols->getOptionColumn()->getDefaultOption();

                $fieldOptionsWrapper = new KlearMatrix_Model_OptionsWrapper;

                foreach ($this->_item->getScreenFieldsOptionsConfig() as $_screen) {

                    $screenOption = new KlearMatrix_Model_ScreenOption;
                    $screenOption->setScreenName($_screen);
                    if ($_screen === $defaultOption) {
                        $screenOption->setAsDefault();
                        $defaultOption = false;
                    }
                    // Recuperamos la configuración del screen, de la configuración general del módulo
                    // Supongo que cuando lo vea Alayn, le gustará mucho :)
                    // El "nombre" mainRouter apesta... pero... O:)

                    $screenOption->setConfig($this->_mainRouter->getConfig()->getScreenConfig($_screen));
                    $fieldOptionsWrapper->addOption($screenOption);
                }

                foreach ($this->_item->getDialogsFieldsOptionsConfig() as $_dialog) {
                    $dialogOption = new KlearMatrix_Model_DialogOption;
                    $dialogOption->setDialogName($_dialog);

                    if ($_dialog === $defaultOption) {
                        $dialogOption->setAsDefault();
                        $defaultOption = false;
                    }

                    $dialogOption->setConfig($this->_mainRouter->getConfig()->getDialogConfig($_dialog));
                    $fieldOptionsWrapper->addOption($dialogOption);

                }


                $data->setFieldOptions($fieldOptionsWrapper);

            }

            $data->fixResults($this->_item);
        }


        $data->setGeneralOptions($this->_item->getScreenOptionsWrapper());


        Zend_Json::$useBuiltinEncoderDecoder = true;

        $jsonResponse = new Klear_Model_DispatchResponse;
        $jsonResponse->setModule('klearMatrix');
        $jsonResponse->setPlugin('list');
        $jsonResponse->addTemplate("/template/paginator","klearmatrixPaginator");
        $jsonResponse->addTemplate("/template/list/type/" . $this->_item->getType(),"klearmatrixList");
        $jsonResponse->addTemplate($cols->getMultiLangTemplateArray("/template/",'list'),"klearmatrixMultiLangList");
        $jsonResponse->addJsFile("/js/plugins/jquery.ui.form.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.template.helper.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.module.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.list.js");
        $jsonResponse->addCssFile("/css/klearMatrix.css");
        $jsonResponse->setData($data->toArray());
        $jsonResponse->attachView($this->view);
    }


}

