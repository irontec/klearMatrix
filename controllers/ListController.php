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

    
    public function templateAction()
    {
    	
    }
    
    public function indexAction()
    {

    	$mainRouter = $this->getRequest()->getParam("mainRouter");
    	$screen = $mainRouter->getCurrentScreen();

    	$mapperName = $screen->getMapperName();
    	
    	$mapper = new $mapperName;
    	//$mapper = new \Mappers\Soap\Brands;
    	
    	$where = null;
    	$order = null;
    	$offset = 0;
		$count = 100;
    	
		
		$cols = $screen->getVisibleColumnWrapper();
		
		
    	$data = new KlearMatrix_Model_KMatrixResponse;
    	
    	$data->setColumnWraper($cols);
    	$data->setPK($screen->getPK());
    	
    	if (!$results= $mapper->fetchListToArray($where,$order,$count,$offset)) {
			// No hay resultados
			$data->setResults(array());
    	
    	} else {
    	    
    		//$results = $screen->filterVisibleResults($results);
    		
    		$data->setResults($results);
    		
    		if ($screen->hasFieldOptions()) {
    		//	$fieldOpts = $screen->getFieldOptions();
    			
    			$fieldOpts = array(
    					array(
    							"screen"=>"screen_editar",
    							"class"=>"ui-silk-page-white-edit",
    							"title"=>"Editar Marca",
    							"noLabel"=>true
    						)
    					
    					);
    			
    			$data->setFieldOptions($fieldOpts);
    			
    		}
    		
    	}
    	
    	
    	Zend_Json::$useBuiltinEncoderDecoder = true;
    	
    	$jsonResponse = new Klear_Model_DispatchResponse();
    	$jsonResponse->setModule('klearMatrix');
    	$jsonResponse->setPlugin('list');
    	$jsonResponse->addTemplate("/list/template","mainkMatrix");
    	$jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.list.js");
    	$jsonResponse->addCssFile("/css/klearMatrix.css");
    	$jsonResponse->setData($data->toJson());
    	$jsonResponse->attachView($this->view);
    	
    }


}

