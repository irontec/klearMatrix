<?php

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

    	$pk = $this->getParam("pk");

//     	$pk = $this->_item->getCurrentPk();

    	if ($pk){
	    	$mapperName = $this->_item->getMapperName();
	    	if(!$mapperName){
	    		throw new Klear_Exception_Default($this->view->translate('MapperName not found in yaml'));
	    	}
	    	$this->_helper->log('Edit for mapper:' . $mapperName . ' > PK('.$pk.')');
	    	$mapper = \KlearMatrix_Model_Mapper_Factory::create($mapperName);
	    	$model = $mapper->find($pk);
	    	if (!$model) {
	    		$this->_helper->log('PK NOT FOUND ' . $mapperName . ' > PK('.$pk.')', Zend_Log::ERR);
	    		throw new Klear_Exception_Default($this->view->translate('Element not found. Cannot edit.'));
	    	}
	    	$columnNames = $model->getColumnsList();
	    	$columnKeys = array_keys($columnNames);
	    	$defaultColummn = $columnNames[$columnKeys[1]];
	    	$defaultTitleSufix = " (".$model->__get($defaultColummn).")";
    	} else {
    		$defaultTitleSufix = "";
    	}
    	$screenTitle = $this->_item->getTitle();
    	$pattern = "/\[format\| *\(%parent%\)\]/";
    	if (preg_match($pattern, $screenTitle)){
    		$screenTitle = preg_replace($pattern, $defaultTitleSufix, $screenTitle);
    	}
    	$this->_item->setName($screenTitle);
    	$data = new KlearMatrix_Model_MatrixResponse;

    	$data
    	->setTitle($screenTitle)
//     	->setPK($this->_item->getPkName())
    	->setPK($pk)
    	->setResponseItem($this->_item);

    	$chartData = array();
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
		    									} else {
		    										$field = $this->_helper->translate($field);
		    									}
		    									$rowN[] = $field;
		    								}
		    								$table[] = $rowN;
		    							}
		    						}
	    						} else if ($chart->table){
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
	    						}
// 	    						echo "<pre>";
// 	    						print_r($table);
// 	    						echo "</pre>";
// 	    						exit;
	    						$gChart["table"] = $table;
	    						$gChart["options"] = $options;
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


//     	$jsonResponse->setPlugin($this->_item->getPlugin('edit'));


    	$jsonResponse->setPlugin("googlecharts");
    	$jsonResponse->addTemplate("/template/googlecharts", "klearmatrixGooglecharts");
    	$jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.googlecharts.js");
    	$jsonResponse->addCssFile("/css/googlecharts.css");

    	$jsonResponse->setData($this->_helper->hookedDataForScreen($this->_item, 'setData', $data));
    	$jsonResponse->attachView($this->_helper->hookedDataForScreen($this->_item, 'attachView', $this->view));









































    	/*
        // Tenemos el $this->_mainRouter para explotar
        // los datos necesarios
        // Do stuff
        $pk = $this->getParam("pk");



        $data = array();


        $data['title'] = Klear_Model_Gettext::gettextCheck($this->_item->getTitle());

        $data['parentIden'] = 'asdg';

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
							if(preg_match("/= *{parent}/", $chart->sql)){
								if(!is_null($pk)){
									$configSQL = preg_replace("/= *{parent}/","= ". $pk, $chart->sql);
								} else {
									$configSQL = preg_replace("/= *{parent}/"," IS NOT NULL", $chart->sql);
								}
							} else {
								$configSQL = $chart->sql;
							}
							$sql = Klear_Model_Gettext::gettextCheck($configSQL);
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

        $jsonResponse = KlearMatrix_Model_DispatchResponseFactory::build();
        $jsonResponse->setPlugin("googlecharts");
        $jsonResponse->addTemplate("/template/googlecharts", "klearmatrixGooglecharts");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.googlecharts.js");
        $jsonResponse->addCssFile("/css/googlecharts.css");
        $jsonResponse->setData($data);
        $jsonResponse->attachView($this->view);*/
    }
}