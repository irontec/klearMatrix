<?php

class KlearMatrix_ListController extends Zend_Controller_Action
{
	
    public function init()
    {
        /* Initialize action controller here */
    	$this->_helper->layout->disableLayout();
    }

    public function templateAction()
    {

    }
    
    public function indexAction()
    {
        
		$maperName = '\Mappers\Soap\Brands';
		
    	$mapper = new $maperName;
		
    	$todas = $mapper->fetchAll();
    	
    	var_dump($todas);
        exit();    	

   	
    }


}

