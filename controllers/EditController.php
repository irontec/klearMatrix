<?php

class KlearMatrix_EditController extends Zend_Controller_Action
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
	    $mapper = new \Mappers\Soap\Brands;
	    
	    $pk = $mainRouter->getParam("pk");
	    $cols = $screen->getVisibleColumnWrapper();
	    
	    $data = new KlearMatrix_Model_KMatrixResponse;
	    
	    $data->setColumnWraper($cols);
	    $data->setPK($screen->getPK());
	    
	    
	    $obj = $mapper->find($pk);
	    
	    $data->setResults(array($obj));
	    
	    
	    $jsonResponse = new Klear_Model_DispatchResponse();
	    $jsonResponse->setModule('klearMatrix');
	    $jsonResponse->setPlugin('edit');
	    $jsonResponse->addTemplate("/edit/template","editkMatrix");
	    $jsonResponse->addJsFile("/js/plugins/jquery.km.edit.js");
	    $jsonResponse->addCssFile("/css/klearMatrixEdit.css");
	    $jsonResponse->setData($data->toJson());
	    $jsonResponse->attachView($this->view);
	    
	}
    
}
