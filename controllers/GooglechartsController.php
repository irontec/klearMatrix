<?php

class KlearMatrix_GooglechartsController extends Zend_Controller_Action
{
    protected $_mainRouter;

    protected $_item;

    public function init()
    {
//     	$cacheManager = $this->getInvokeArg('bootstrap')->getResource('cachemanager');
//     	$cache = $cacheManager->getCache('klearmatrixGooglecharts');
//     	$cache->start();
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
        // Tenemos el $this->_mainRouter para explotar
        // los datos necesarios

        // Do stuff
        $pk = $this->getParam("pk");
		if(!$pk){
			$pk = "null";
		}
        $data = array();

        $data['title'] = $this->_item->getTitle();
//         $data['title'] = $pk;

		//Generar gráficos
        $data["chartGroups"] = array();
        $chartGroups = $this->_item->getRawConfigAttribute("chartGroups");
        foreach ($chartGroups as $chartGroup){
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

        		//$data["chartGroups"][$title] = array();
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
							if(preg_match("/{parent} *and/",$chart->sql)){
								$configSQL = preg_replace("/{parent} *(and | AND | And)/", $pk." and ", $chart->sql);
							}elseif(preg_match("/{parent}/",$chart->sql)){
								$configSQL = preg_replace("/{parent}/", $pk." ", $chart->sql);
							}else{
								$configSQL = $chart->sql;
							}
							$sql = Klear_Model_Gettext::gettextCheck($configSQL);
// 							echo "<p>".$chart->sql."</p>";
// 							echo "<p>".$configSQL."</p>";
// 							echo "<p>".$sql."</p>";
// 							die(1);
	        				$options = array();
	        				if (isset($chart->options)){
		        				foreach ($chart->options->toArray() as $key => $value){
		        					if(!is_array($value)){
		        						$options[$key] = Klear_Model_Gettext::gettextCheck($value);
		        					} else {
		        						foreach ($value as $key2 => $value2){
		        							$options[$key][$key2] = Klear_Model_Gettext::gettextCheck($value2);
		        						}
		        					}

		        				}
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
	        				$stmt = $dbAdapter->query($sql);
	        				$results = $stmt->fetchAll();
	        				$gChart["table"] = array();
	        				$table = array();
	        				if(count($results)>0){
	        					$table[] = array_keys($results[0]);
	        					foreach ($results as $row){
	        						$rowN = array();
	        						foreach($row as $field){
	        							if (is_numeric($field)){
	        								$field = floatval($field);
	        							} else {
	        								$field = $this->_helper->translate($field);
	        							}
	        							$rowN[] = $field;
	        						}
	        						$table[] = $rowN;
	        					}
	        				}
	        				$gChart["table"] = $table;
	        				$gChart["options"] = $options;
	        				$group["charts"][] = $gChart;

	        			}
	        		}
        		}
        		$data["chartGroups"][] = $group;
        	}
        }

        // Hay que devolver un objeto Klear_Model_DispatchResponse()
//         $jsonResponse = new Klear_Model_DispatchResponse();
        $jsonResponse = KlearMatrix_Model_DispatchResponseFactory::build();
        $jsonResponse->setPlugin("googlecharts");
//         $jsonResponse->setModule('default');
        $jsonResponse->addTemplate("/template/googlecharts", "klearmatrixGooglecharts");

        // Forzamos dependencias (solo daría problemas en entornos de desarrollo, ya que en producción se sirve todo en el bundle)
//         $jsonResponse->addJsFile("/../klearMatrix/js/plugins/jquery.ui.form.js");
//         $jsonResponse->addJsFile("/../klearMatrix/js/plugins/jquery.klearmatrix.template.helper.js");
//         $jsonResponse->addJsFile("/../klearMatrix/js/translation/jquery.klearmatrix.translation.js");
//         $jsonResponse->addJsFile("/../klearMatrix/js/plugins/jquery.klearmatrix.module.js");
//         $jsonResponse->addCssFile("/../klearMatrix/css/klearMatrix.css");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.googlecharts.js");
        $jsonResponse->addCssFile("/css/googlecharts.css");
        $jsonResponse->setData($data);
        $jsonResponse->attachView($this->view);
    }
}