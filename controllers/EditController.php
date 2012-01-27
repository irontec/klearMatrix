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

    
    
    public function saveAction() {
    	
    	
    	
    }
    
    
    public function indexAction()
    {
	    
	    $mainRouter = $this->getRequest()->getParam("mainRouter");
	    $item = $mainRouter->getCurrentItem();
	    
	    $mapperName = $item->getMapperName();
	    $mapper = new $mapperName;
	    	    
	    $pk = $mainRouter->getParam("pk");
	    $cols = $item->getVisibleColumnWrapper();
	    
	    $data = new KlearMatrix_Model_MatrixResponse;
	    
	    $data
	        ->setTitle($item->getTitle())
	        ->setColumnWraper($cols)
	        ->setPK($item->getPK());
	    
	    if (!$obj = $mapper->find($pk)) {
	    	// Error
	    	
	    } else {
	    	$data->setResults($obj)
	    	        ->fixResults($item);	
	    }
	    
	    Zend_Json::$useBuiltinEncoderDecoder = true;
	    
	    $jsonResponse = new Klear_Model_DispatchResponse();
	    $jsonResponse->setModule('klearMatrix');
	    $jsonResponse->setPlugin('edit');
	    $jsonResponse->addTemplate("/template/edit/type/" . $item->getType(),"klearmatrixEdit");
	    $jsonResponse->addTemplateArray($cols->getTypesTemplateArray("/template/field/type/","clearMatrixFields"));
	    $jsonResponse->addJsFile("/js/plugins/jquery.h5validate.js");
	    $jsonResponse->addJsFile("/js/scripts/2.5.3-crypto-md5.js");
	    $jsonResponse->addJsFile("/js/plugins/jquery.ui.form.js");
	    $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.module.js");
	    $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.edit.js");
	    $jsonResponse->addCssFile("/css/klearMatrixEdit.css");
	    $jsonResponse->setData($data->toArray());
	    $jsonResponse->attachView($this->view);
	    
	}
    
}
