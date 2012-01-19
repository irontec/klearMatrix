<?php

class KlearMatrix_DeleteController extends Zend_Controller_Action
{
	
    public function init()
    {
        /* Initialize action controller here */
    	$this->_helper->layout->disableLayout();
    	
    	$this->_helper->ContextSwitch()
    		->addActionContext('index', 'json')
    		->addActionContext('delete', 'json')
    		->initContext('json');
    }

    
    public function indexAction() {
    	
    	
    	$mainRouter = $this->getRequest()->getParam("mainRouter");
    	$item = $mainRouter->getCurrentItem();
    	
    	$mapperName = $item->getMapperName();
    	$mapper = new $mapperName;
    	
    	$pk = $mainRouter->getParam("pk");
    	
    	$cols = $item->getVisibleColumnWrapper();
    	
    	$defaultCol = $cols->getDefaultCol();
    	
    	$cols
    		->resetWrapper()
    		->addCol($defaultCol);
    	
    	$data = new KlearMatrix_Model_MatrixResponse;
    	
    	$data->setColumnWraper($cols);
    	$data->setPK($item->getPK());
    	
    	if (!$obj = $mapper->find($pk)) {
    		// Error
    	
    	} else {
    		$data->setResults($obj);
    		$data->fixResults($item);
    	}
    	
    	$jsonResponse = new Klear_Model_DispatchResponse();
    	$jsonResponse->setModule('klearMatrix');
    	$jsonResponse->setPlugin('delete');
    	$jsonResponse->addTemplate("/template/delete/type/" . $item->getType(),"klearmatrixDelete");
    	$jsonResponse->addTemplateArray($cols->getTypesTemplateArray("/template/field/type/","clearMatrixFields"));
    	$jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.module.js");
    	$jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.delete.js");
    	$jsonResponse->addCssFile("/css/klearMatrixEdit.css");
    	$jsonResponse->setData($data->toArray());
    	$jsonResponse->attachView($this->view);
    	
    	
    }
    
    
    public function deleteAction()
    {
    
  		$mainRouter = $this->getRequest()->getParam("mainRouter");
    	$item = $mainRouter->getCurrentItem();
    	
    	$mapperName = $item->getMapperName();
    	$mapper = new $mapperName;
    	
    	
    	$sResponse = new Klear_Model_SimpleResponse;
    	
    	
    	$pk = $mainRouter->getParam("pk");
    	
    	
    	// TO-DO traducir mensaje?
    	// TO-DO lanzar excepción ?
    	// Recuperamos el objeto y realizamos la acción de borrar
    	if ( ($obj = $mapper->find($pk)) &&
    			($mapper->delete($obj)) ) {
    		$data = array(
    					'error'=>false,
    					'pk'=>$pk,
    					'message'=>'Registro Eliminado correctamente'
    				);
    	} else {
    		$data = array(
    				'error'=>true,
    				'message'=>'Algún error eliminado el registro'
    		);
    		
    	}
    	
    	$jsonResponse = new Klear_Model_SimpleResponse();
    	$jsonResponse->setData($data);
    	$jsonResponse->attachView($this->view);
    	
    
    }
    
} 