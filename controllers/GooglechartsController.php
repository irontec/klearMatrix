<?php

/**
 * Controlador que muestra gráficos de Google Charts
 *
 * @author luis
 *
 */

class KlearMatrix_GooglechartsController extends Zend_Controller_Action
{
    protected $_mainRouter;

    protected $_item;

    public function init()
    {
        /* Initialize action controller here */
        $this->_helper->layout->disableLayout();
        $this->_helper->ContextSwitch()
            ->addActionContext('index', 'json')
            ->initContext('json');

        if ((!$this->_mainRouter = $this->getRequest()->getUserParam("mainRouter")) || (!is_object($this->_mainRouter)) ) {
            throw New Zend_Exception('',Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION);
        }
        $this->_item = $this->_mainRouter->getCurrentItem();
    }

    public function indexAction()
    {

        $id = $this->getParam("pk");
        $parentId = $this->getParam("parentId");
        if($id){
            $pk = $id;
            $mapperName = $this->_item->getMapperName();
        } else if ($parentId){
            $pk = $parentId;
            $parentScreenName = $this->getParam("parentScreen");
            $parentConfig = $this->_mainRouter->getConfig()->getScreenConfig($parentScreenName);
            $mapperName = $parentConfig->mapper;
        } else {
            $pk = false;
        }
//         $pk = $this->_item->getCurrentPk();

        if ($pk){
//             $mapperName = $this->_item->getMapperName();
            if(!$mapperName){
                throw new Klear_Exception_Default($this->view->translate('MapperName not found in yaml'));
            }
            $this->_helper->log('Edit for mapper:' . $mapperName . ' > PK('.$pk.')');
            $mapper = \KlearMatrix_Model_Mapper_Factory::create($mapperName);
            $model = $mapper->find($pk);
            if (!$model) {
                $this->_helper->log('PK NOT FOUND ' . $mapperName . ' > PK('.$pk.')', Zend_Log::ERR);
                throw new Klear_Exception_Default($this->view->translate('PK '.$pk.' not found. Cannot edit.'));
            }

            $columnNames = $model->getColumnsList();
            $columnKeys = array_keys($columnNames);
            $defaultColummn = $columnNames[$columnKeys[1]];
            $screenTitle = $this->_item->getTitle();
            $pattern = "/\[format\|.*\%parent.*%.*\]/";
            $customParentField = $this->_item->getRawConfigAttribute("parentField");
            if ($customParentField){
                $defaultColummn = $customParentField;
            }
//             if (preg_match($pattern, $screenTitle)){
//                 $patternParts = explode("%", $screenTitle);
//                 $paternContent = $patternParts[1];
//                 $patternContentParts = explode(".", $paternContent);
//                 if(count($patternContentParts)> 1){
//                     $defaultColummn = $patternContentParts[1];
//                 }
//             }
            $defaultTitleSufix = $model->__get($defaultColummn);
        } else {
            $defaultTitleSufix = "";
        }
//         $screenTitle = $this->_item->getTitle();

//         $pattern = "/\[format\| *\(%parent%\)\]/";
//         if (preg_match($pattern, $screenTitle)){
//             $screenTitle = preg_replace($pattern, $defaultTitleSufix, $screenTitle);
//         }
        $screenTitle = $this->_item->getTitle();
        $pattern = "/\[format\|.*\%parent.*%.*\]/";
        if (preg_match($pattern, $screenTitle)){
            $titleParts = explode("[format|",$screenTitle);
            $parentSufix = $titleParts[1];
            $parentSufix = trim($parentSufix, "]");
            $parentSufix = str_replace(trim($parentSufix," ()"), $defaultTitleSufix, $parentSufix);
            $screenTitle = $titleParts[0].$parentSufix;
//             $screenTitle = $parentSufix;
//             $screenTitle = preg_replace($pattern, $defaultTitleSufix, $screenTitle);
        }

        $this->_item->setName($screenTitle);
        $data = new KlearMatrix_Model_MatrixResponse;

        $data
        ->setTitle($screenTitle)
//         ->setPK($this->_item->getPkName())
        ->setPK($pk)
        ->setResponseItem($this->_item);


        $chartData = array();
        $dashboardPannel = $this->_item->getRawConfigAttribute("dashboardPannel");
        
        if ( $dashboardPannel && $dashboardPannel->show !== false){
            $chartData["dashboard"] = $this->_dashboard($dashboardPannel);
        }
        
        $chartData["title"] = $screenTitle;
        $chartData["chartGroups"] = array();
        $chartGroups = $this->_item->getRawConfigAttribute("chartGroups");
        if($chartGroups){
            foreach ($chartGroups as $chartGroup){
                if(!$chartGroup){
                    continue;
                }
                if(!is_null($chartGroup->show)){
                    $showGroup = $chartGroup->show;
                } else {
                    $showGroup = true;
                }
                if($showGroup){
                    $group = array();
                    if(isset($chartGroup->title)){
                        $title = Klear_Model_Gettext::gettextCheck($chartGroup->title);
                        $group["title"] = $title;
                    }
                    if(isset($chartGroup->comment)){
                        $group["comment"] = Klear_Model_Gettext::gettextCheck($chartGroup->comment);
                    }

                    //$chartData["chartGroups"][$title] = array();
                    $group["charts"] = array();
                    if(isset($chartGroup->charts)){
                        foreach ($chartGroup->charts as $key => $chart) {

                            if(isset($chart->show)){
                                $show = $chart->show;
                            } else {
                                $show = true;
                            }
                            if($show){
                                $chartTitle = Klear_Model_Gettext::gettextCheck($chart->title);
                                if($chart->sql){
                                    if(preg_match("/= *%parent%/", $chart->sql)){
                                        if(!is_null($pk)){
                                            $configSQL = preg_replace("/= *%parent%/","= ". $pk, $chart->sql);
                                        } else {
                                            $configSQL = preg_replace("/= *%parent%/"," IS NOT NULL", $chart->sql);
                                        }
                                    } else {
                                        $configSQL = $chart->sql;
                                    }
                                    $sql = Klear_Model_Gettext::gettextCheck($configSQL);
                                } else {
                                    $sql = null;
                                }

                                $options = array();
                                if (isset($chart->options)){
                                    foreach ($chart->options->toArray() as $key => $value){
                                        if(!is_array($value)){
                                            $options[$key] = Klear_Model_Gettext::gettextCheck($value);
                                        } else {
                                            foreach ($value as $key2 => $value2){
                                                if(!is_array($value2)){
                                                    $options[$key][$key2] = Klear_Model_Gettext::gettextCheck($value2);
                                                } else {
                                                    foreach ($value2 as $key3 => $value3){
                                                        $options[$key][$key2][$key3] = Klear_Model_Gettext::gettextCheck($value3);
                                                    }
                                                }
                                            }
                                        }

                                    }
                                }
                                if (isset($chart->controls)){
                                    $controlOptions = $chart->controls->toArray();
                                    $allowedPositions = array ('top','right','bottom','left');
                                    if (isset($chart->controls->position) && !in_array($chart->controls->position, $allowedPositions)){
                                        $message = "controls position '".$chart->controls->position."' not alowed";
                                        throw New Zend_Exception($message,Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION);
                                    }
                                } else {
                                    $controlOptions = false;
                                }
                                if (isset($chart->view)){
                                    $viewColumns = $chart->view->toArray();
                                }else {
                                    $viewColumns = false;
                                }

                                $gChart = array();
                                $gChart["title"] = $chartTitle;
                                if(isset($chart->comment)){
                                    $gChart["comment"] = Klear_Model_Gettext::gettextCheck($chart->comment);
                                }
                                if(isset($chart->legend)){
                                    $gChart["legend"] = Klear_Model_Gettext::gettextCheck($chart->legend);
                                }
                                if (isset($chart->type)){
                                    $gChart["type"] = $chart->type;
                                } else {
                                    $gChart["type"] = "ColumnChart";
                                }

                                $gChart["hAxis"] = array();
                                $gChart["hAxis"]["values"] = array ();
                                $gChart["vAxis"] = array();
                                $gChart["vAxis"]["values"] = array ();
                                $dbAdapter = Zend_Db_Table::getDefaultAdapter();
                                $gChart["table"] = array();
                                $table = array();

                                if ($sql){
                                    $stmt = $dbAdapter->query($sql);
                                    $results = $stmt->fetchAll();
                                    if(count($results)>0){
                                        $tableHeaders = array_keys($results[0]);
                                        $tableHeadersTranslated = array();
                                        if(isset($chart->cols)){
                                            foreach ($tableHeaders as $tableHeader){
                                                if(isset($chart->cols->$tableHeader)){
                                                    $tableHeadersTranslated[] = Klear_Model_Gettext::gettextCheck($chart->cols->$tableHeader);
                                                }else {
                                                    $tableHeadersTranslated[] = $tableHeader;
                                                }
                                            }
                                        } else {
                                            $tableHeadersTranslated = $tableHeaders;
                                        }
                                        $table[] = $tableHeadersTranslated;
                                        foreach ($results as $row){
                                            $rowN = array();
                                            foreach($row as $field){
                                                if (is_numeric($field)){
                                                    $field = floatval($field);
//                                                 } else if (DateTime::createFromFormat('Y-m-d G:i:s', $field) !== FALSE){
//                                                     $date = new DateTime($field);
//                                                     $field = $date->format('Y,m,d,G,i,s');
                                                } else {
                                                    $field = $this->_helper->translate($field);
                                                }
                                                $rowN[] = $field;
                                            }
                                            $table[] = $rowN;
                                        }
                                    }
                                } else if ($chart->table) {
                                    foreach ($chart->table as $value){
                                        $cols = explode("|", $value);
                                        $colsNew = array();
                                        foreach ($cols as $col){
                                            if (is_numeric(trim($col))){
                                                $field = floatval(trim($col));
                                            } else {
                                                if (trim($col) == "null"){
                                                    $field = null;
                                                } else {
                                                    $field = $this->_helper->translate(trim($col));
                                                }
                                            }
                                            $colsNew[]=$field;
                                        }
                                        $table[]=$colsNew;
                                    }

                                } else if ($chart->analytics) {

                                    $analytics = new KlearMatrix_Model_ChartsData_Analytics($chart->analytics);
                                    $table = $analytics->getAnalyticsData();

                                }

//                                 echo "<pre>";
//                                 print_r($table);
//                                 echo "</pre>";
//                                 exit;

                                $gChart["table"] = $table;
                                $gChart["options"] = $options;
                                $gChart["controls"] = $controlOptions;
                                $gChart["view"] = $viewColumns;
                                $group["charts"][] = $gChart;

                            }
                        }
                    }
                    $chartData["chartGroups"][] = $group;
                }

            }
        }
        $data->setResults($chartData);


        $parentScreenName = $this->getRequest()->getPost("parentScreen", false);
        if (!$parentScreenName) {
            $parentScreenName = $this->getRequest()->getPost("callerScreen", false);
        }

        if (false !== $parentScreenName) {
            $data->calculateParentData($this->_mainRouter, $parentScreenName, $pk);
        }

        $data->parseItemAttrs($this->_item);

        $jsonResponse = KlearMatrix_Model_DispatchResponseFactory::build();


//         $jsonResponse->setPlugin($this->_item->getPlugin('edit'));


        $jsonResponse->setPlugin("googlecharts");
        $jsonResponse->addTemplate("/template/googlecharts", "klearmatrixGooglecharts");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.googlecharts.js");
        $jsonResponse->addCssFile("/css/googlecharts.css");

        $jsonResponse->setData($this->_helper->hookedDataForScreen($this->_item, 'setData', $data));
        $jsonResponse->attachView($this->_helper->hookedDataForScreen($this->_item, 'attachView', $this->view));

    }

    protected function _dashboard($config)
    {
        $data['title'] = Klear_Model_Gettext::gettextCheck($config->title);

        if ($config->useExplain) {
            $data['title'] .= ' ' . $this->_helper->translate('(Aproximated values)');
        }

        $sectionsBlackList = array();
        if ($config->sectionsBlackList) {
            $sectionsBlackList = $config->sectionsBlackList;
            $sectionsBlackList = $sectionsBlackList->toArray();
        }

        $menuConfig = Zend_Controller_Front::getInstance()
        ->getParam('bootstrap')
        ->getResource('modules')
        ->offsetGet('klear')
        ->getOption('menu');

        $data['sections'] = array();

        foreach ($menuConfig as $section) {
            $sectionTmp = array(
                    'name' => $section->getName(),
                    'subsects' => array()
            );

            foreach ($section as $subsection) {

                $file = $subsection->getMainFile();

                $sectionConfig = new Klear_Model_SectionConfig;
                $sectionConfig->setFile($file);
                if (!$sectionConfig->isValid()) {
                    continue;
                    return;
                }

                if (in_array($file, $sectionsBlackList)) {
                    continue;
                }

                // Nos devuelve el configurador del módulo concreto instanciado.
                $moduleConfig = $sectionConfig->factoryModuleConfig();
                $moduleRouter = $moduleConfig->buildRouterConfig();
                $moduleRouter->resolveDispatch();

                if ($moduleRouter->getCurrentItem()->getRawConfigAttribute("dashboard->class")) {

                    $dashElementClassName = $moduleRouter->getCurrentItem()->getRawConfigAttribute("dashboard->class");
                    $dashSection = new $dashElementClassName;
                    $dashSection->setConfig($moduleRouter->getCurrentItem()->getRawConfigAttribute("dashboard"));
                    $dashSection->setItem($moduleRouter->getCurrentItem());
                    $sectionTmp['subsects'][] = array(
                            'name' => $dashSection->getName(),
                            'class' => $dashSection->getClass(),
                            'file' => $dashSection->getFile(),
                            'subtitle' => $dashSection->getSubTitle()
                    );

                    continue;
                }

                /*
                 * Para KlearMatrix List, se calcula automáticamente.
                */
                if (($moduleRouter->getModuleName() == "klearMatrix") &&
                        ($moduleRouter->getControllerName() == "list") ) {
                    $sectionTmp['subsects'][] = $this->_calculateForKlearMatrixList($moduleRouter, $subsection, $config);
                    continue;
                }
            }

            if (sizeof($sectionTmp['subsects'])>0) {
                $data['sections'][] = $sectionTmp;
            }

        }
        return $data;
    }
    protected function _calculateForKlearMatrixList($moduleRouter, $subsection, $config)
    {
        $_item = $moduleRouter->getCurrentItem();

        $_mapper = \KlearMatrix_Model_Mapper_Factory::create($_item->getMapperName());

        //$cols = $_item->getVisibleColumns();
        $model = $_item->getObjectInstance();
        $fakeData = new KlearMatrix_Model_MatrixResponse();

        /**
         * El primer paramétro de createListWhere solamente se usa para construir
         * la condición para filtrar resultados en ListControllers
         */
        $where = $this->_helper->createListWhere(new KlearMatrix_Model_ColumnCollection(), $model, $fakeData, $_item);

        if (!$where) {
            $totalItems = $_mapper->countAllRows($config->useExplain);
        } else {
            $totalItems = $_mapper->countByQuery($where);
        }

        return array(
                'name' => $subsection->getName(),
                'class' => $subsection->getClass(),
                'file' => $subsection->getMainFile(),
                'subtitle' => $totalItems
        );

    }

}