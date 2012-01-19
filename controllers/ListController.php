<?php

class KlearMatrix_ListController extends Zend_Controller_Action
{
	
    public function init()
    {
        /* Initialize action controller here */
    	$this->_helper->layout->disableLayout();
    	
    	$this->_helper->ContextSwitch()
    		->addActionContext('index', 'json')
    		->initContext('json');
    }

   
    public function indexAction()
    {

    	$mainRouter = $this->getRequest()->getParam("mainRouter");
    	$item = $mainRouter->getCurrentItem();

    	$mapperName = $item->getMapperName();
    	
    	$mapper = new $mapperName;
    	//$mapper = new \Mappers\Soap\Brands;
    	
    	$where = null;
    	$order = null;
    	$offset = 0;
		$count = 100;
    	
		
		$cols = $item->getVisibleColumnWrapper();
		
		
    	$data = new KlearMatrix_Model_MatrixResponse;
    	
    	$data->setColumnWraper($cols);
    	$data->setPK($item->getPK());
    	
    	if (!$results= $mapper->fetchList($where,$order,$count,$offset)) {
			// No hay resultados
			$data->setResults(array());
    	
    	} else {
    	    
    		//$results = $item->filterVisibleResults($results);
    		
    		$data->setResults($results);
    		
    		if ($item->hasFieldOptions()) {
    			
    			$fieldOptionsWrapper = new KlearMatrix_Model_FieldOptionsWrapper;
    			
    			foreach ($item->getScreenFieldsOptionsConfig() as $_screen) {
    				
    				$screenOption = new KlearMatrix_Model_ScreenFieldOption;
    				$screenOption->setScreenName($_screen);
    				// Recuperamos la configuración del screen, de la configuración general del módulo
    				// Supongo que cuando lo vea Alayn, le gustará mucho :)
    				// El "nombre" mainRouter apesta... pero... O:)
    				$screenOption->setConfig($mainRouter->getConfig()->getScreenConfig($_screen));
    				$fieldOptionsWrapper->addOption($screenOption);
    			}

    			foreach ($item->getDialogsFieldsOptionsConfig() as $_dialog) {
    				$dialogOption = new KlearMatrix_Model_DialogFieldOption;
    				$dialogOption->setDialogName($_dialog);
    				$dialogOption->setConfig($mainRouter->getConfig()->getDialogConfig($_dialog));
    				$fieldOptionsWrapper->addOption($dialogOption);
    				
    			}
    			
    			
    			$data->setFieldOptions($fieldOptionsWrapper);
    			
    		}
    		$data->fixResults($item);
    	}
    	

    	$generalOptionsWrapper = new KlearMatrix_Model_GeneralOptionsWrapper;
    	
    	foreach($item->getScreensGeneralOptionsConfig() as $_screen) {
    		$screenOption = new KlearMatrix_Model_ScreenGeneralOption;
    		$screenOption->setScreenName($_screen);
    		$screenOption->setConfig($mainRouter->getConfig()->getScreenConfig($_screen));
    		$generalOptionsWrapper->addOption($screenOption);
    	}
    	
    	// TO-DO > Opciones generales de dialogo y comprobar checkboxes
    	
    	$data->setGeneralOptions($generalOptionsWrapper);
    	
    	
    	Zend_Json::$useBuiltinEncoderDecoder = true;
    	
    	$jsonResponse = new Klear_Model_DispatchResponse;
    	$jsonResponse->setModule('klearMatrix');
    	$jsonResponse->setPlugin('list');
    	$jsonResponse->addTemplate("/template/list/type/" . $item->getType(),"klearmatrixList");
    	
    	$jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.module.js");
    	$jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.list.js");
    	$jsonResponse->addCssFile("/css/klearMatrix.css");
    	$jsonResponse->setData($data->toArray());
    	$jsonResponse->attachView($this->view);
    	
    }


}

