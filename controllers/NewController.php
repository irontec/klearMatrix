<?php

class KlearMatrix_NewController extends Zend_Controller_Action
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

        $object = $this->_item->getObjectInstance();

        $cols = $this->_item->getVisibleColumnWrapper();

        foreach($cols as $column) {
			if ($column->isOption()) continue;
			$value = $this->getRequest()->getPost($column->getDbName());

			//TO-DO: VALIDADORES!!!
			if (empty($value)) {

			    continue;
			}

			$setterMethod = "set" . $object->columnNameToVar($column->getDbName());
			$object->$setterMethod($value);
		}

		try {
		     $object->save();
             $data = array(
    			'error'=>false,
    			'pk'=>$object->getPrimaryKey(),
    			'message'=>'Registro añadido correctamente.'
    	    );

		} catch (Zend_Exception $exception) {
		    $data = array(
    				'error'=>true,
    				'message'=>'Error añadiendo el registro.'
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

	    $cols = $this->_item->getVisibleColumnWrapper();

	    $data = new KlearMatrix_Model_MatrixResponse;

	    $data->setResponseItem($this->_item)
	        ->setTitle($this->_item->getTitle())
	        ->setColumnWraper($cols);

	    Zend_Json::$useBuiltinEncoderDecoder = true;

	    $jsonResponse = new Klear_Model_DispatchResponse();
	    $jsonResponse->setModule('klearMatrix');
	    $jsonResponse->setPlugin('klearMatrixNew');
	    $jsonResponse->addTemplate("/template/new/type/" . $this->_item->getType(),"klearmatrixNew");
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
