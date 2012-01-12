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
    	
    	$maperName = $mainRouter->getMapperName();
    	$mapper = new $maperName;
    	
    	$where = null;
    	$order = null;
    	$limit = 100;
    	
    	$jsonResponse = new Klear_Model_DispatchResponse();
    	$jsonResponse->setModule('klearMatrix');
    	
    	$jsonResponse->addTemplate("/list/template");
    	$jsonResponse->addJsFile("/js/plugins/jquery.ui.klearMatrix.js");
    	$jsonResponse->addCssFile("/css/klearMatrix.css");
    	
    	$data = array(
    		"fields"=>array()
    	);

    	if (!$results= $mapper->fetchAll()) {
			// No hay resultados    		
    		
    	} else {
    		
    		
    		$data['columns'] = array("Brand Name","Brand Description","Opciones");
    		$data['fields'] = $results;
    		
    	}
    	
    	$jsonResponse->setData($data);
    	
    	$jsonResponse->attachView($this->view);
    }


}

