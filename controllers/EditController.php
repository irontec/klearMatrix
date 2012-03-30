<?php

class KlearMatrix_EditController extends Zend_Controller_Action
{

    /**
     * Route Dispatcher desde klear/index/dispatch
     * @var KlearMatrix_Model_RouteDispatcher
     */
    protected $_mainRouter;

    /**
     * Screen|Dialog
     * @var KlearMatrix_Model_ResponseItem
     */
    protected $_item;


    public function init()
    {
        /* Initialize action controller here */
    	$this->_helper->layout->disableLayout();

    	$this->_helper->ContextSwitch()
    		->addActionContext('index', 'json')
    		->addActionContext('save', 'json')
    		->initContext('json');

    	$this->_mainRouter = $this->getRequest()->getParam("mainRouter");
    	$this->_item = $this->_mainRouter->getCurrentItem();

    }



    public function saveAction() {
        
        

    	$mapperName = $this->_item->getMapperName();
    	$mapper = new $mapperName;
    	$pk = $this->_mainRouter->getParam("pk");


    	// TO-DO traducir mensaje?
    	// TO-DO lanzar excepción ?
    	// Recuperamos el objeto y realizamos la acción de borrar

    	if (!$object = $mapper->find($pk)) {
    	    Throw new Zend_Exception('El registro no se encuentra almacenado.');
    	}

    	$cols = $this->_item->getVisibleColumnWrapper();

    	$hasDependant = false;
    	
        foreach($cols as $column) {
			if ($column->isOption()) continue;
			if ($column->isReadonly()) continue;
			if (!$setter = $column->getSetterName($object)) continue;
			if (!$getter = $column->getGetterName($object)) continue;
			
			if ($column->isMultilang()) {
			    $value = array();
                foreach($cols->getLangs() as $lang) {
                    $value[$lang] = $this->getRequest()->getPost($column->getDbName() . $lang);
                }
                
			} else {

			    $value = $this->getRequest()->getPost($column->getDbName());

			}

		    switch(true) {
		        case ($column->isMultilang()):
		            foreach($value as $lang => $_value) {
		                $_value =  $column->filterValue($_value,$object->{$getter}($lang));
		                $object->$setter($_value,$lang);
		            }
		            break;
		            
		        case ($column->isDependant()):
		            $value = $column->filterValue($value,$object->{$getter}());
		            $object->$setter($value,true);
		            $hasDependant = true;
		            break;
		            
		        case ($column->isFile()):
		            
		            $value = $column->filterValue($value,$object->{$getter}());
		            if ($value !== false) {
		                $object->$setter($value['path'],$value['basename']);
		            }
		            
		            break;
		            
		        
		        default:
		            $object->$setter($value);
		         
		     }
		    
			    

		}


		try {
		     if (!$pk = $object->save(false,$hasDependant)) {
		         
		         Throw New Zend_Exception("Error salvando el registro.");
		     }
		     
		     

             $data = array(
    			'error'=>false,
    			'pk'=>$object->getPrimaryKey(),
    			'message'=>'Registro modificado correctamente.'
    	    );

		} catch (Zend_Exception $exception) {

		    $data = array(
    				'error'=>true,
    				'message'=> $exception->getMessage()
    		);


		}

		$jsonResponse = new Klear_Model_SimpleResponse();
    	$jsonResponse->setData($data);
    	$jsonResponse->attachView($this->view);

    }


    public function indexAction()
    {

	    $mapperName = $this->_item->getMapperName();
	    $mapper = new $mapperName;

	    $pk = $this->_mainRouter->getParam("pk");
	    $cols = $this->_item->getVisibleColumnWrapper();

	    $data = new KlearMatrix_Model_MatrixResponse;

	    $data
	        ->setTitle($this->_item->getTitle())
	        ->setColumnWraper($cols)
	        ->setPK($this->_item->getPK())
	        ->setResponseItem($this->_item);

	    if (!$model = $mapper->find($pk)) {
	    	exit;// Error

	    } else {
	        
	        
	    	$data->setResults($model)
	    	        ->fixResults($this->_item);
	    }

	    Zend_Json::$useBuiltinEncoderDecoder = true;

	    $jsonResponse = new Klear_Model_DispatchResponse();
	    $jsonResponse->setModule('klearMatrix');
	    $jsonResponse->setPlugin('edit');
	    $jsonResponse->addTemplate("/template/edit/type/" . $this->_item->getType(),"klearmatrixEdit");
	    $jsonResponse->addTemplateArray($cols->getTypesTemplateArray("/template/field/type/","klearMatrixFields"));
	    $jsonResponse->addTemplate($cols->getMultiLangTemplateArray("/template/",'field'),"klearmatrixMultiLangField");
	    
	    
	    $jsonResponse->addJsFile("/js/plugins/jquery.h5validate.js");

	    $jsonResponse->addJsFile("/js/plugins/jquery.autoresize.js");
	    $jsonResponse->addJsFile("/js/scripts/2.5.3-crypto-md5.js");
	    $jsonResponse->addJsFile("/js/plugins/jquery.ui.form.js");
	    $jsonResponse->addJsArray($cols->getColsJsArray());
	    $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.module.js");
	    $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.edit.js");
	    $jsonResponse->addCssFile("/css/klearMatrixEdit.css");
	    $jsonResponse->addCssArray($cols->getColsCssArray());
	    $jsonResponse->setData($data->toArray());
	    $jsonResponse->attachView($this->view);

	}

}
