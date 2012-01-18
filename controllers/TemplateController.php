<?php

class KlearMatrix_TemplateController extends Zend_Controller_Action
{
	
    public function init()
    {
        /* Initialize action controller here */
    	$this->_helper->layout->disableLayout();
    	
    }

    
    public function listAction()
    {
    
    }
    
    
    
    public function editAction()
    {
    
    }
    
    
    public function deleteAction()
    {
    
    }
    
    
    public function fieldAction()
    {
    	if ($fieldType = $this->getRequest()->getParam("type")) {
    
    		switch($fieldType) {
    			case "text":
    			case "textarea":
    				$this->_helper->viewRenderer('fields/' . $fieldType);
    				break;
    
    		}
    	}
    
    
    }
    
}