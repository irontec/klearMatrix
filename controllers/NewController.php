<?php

class KlearMatrix_NewController extends Zend_Controller_Action
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
	    	    
	    
	    $cols = $item->getVisibleColumnWrapper();
	    
	    $data = new KlearMatrix_Model_MatrixResponse;
	    
	    $data->setColumnWraper($cols);
	    
	    Zend_Json::$useBuiltinEncoderDecoder = true;
	    
	    $jsonResponse = new Klear_Model_DispatchResponse();
	    $jsonResponse->setModule('klearMatrix');
	    $jsonResponse->setPlugin('new');
	    $jsonResponse->addTemplate("/template/new/type/" . $item->getType(),"klearmatrixNew");
	    $jsonResponse->addTemplateArray($cols->getTypesTemplateArray("/template/field/type/","clearMatrixFields"));
	    $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.module.js");
	    $jsonResponse->addJsFile("/js/scripts/2.5.3-crypto-md5.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.h5validate.js");
	    $jsonResponse->addJsFile("/js/plugins/jquery.ui.form.js");	    
	    $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.edit.js"); // klearmatrix.new hereda de klearmatrix.edit
	    $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.new.js");
	    $jsonResponse->addCssFile("/css/klearMatrixNew.css");
	    $jsonResponse->setData($data->toArray());
	    $jsonResponse->attachView($this->view);
	    
	}
    
}
