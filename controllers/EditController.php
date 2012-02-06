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

        foreach($cols as $column) {
			if ($column->isOption()) continue;

			if ($column->isMultilang()) {
			    $value = array();
                foreach($cols->getLangs() as $lang) {
                    $value[$lang] = $this->getRequest()->getPost($column->getDbName().$lang);
                }
			} else {

			    $value = $this->getRequest()->getPost($column->getDbName());

			}


			if (!$setter = $column->getSetterName($object)) continue;
			if (!$getter = $column->getGetterName($object)) continue;

			if ($column->isMultilang()) {
			    foreach($value as $lang => $_value) {
			        $_value =  $column->filterValue($_value,$object->{$getter}($lang));
			        $object->$setter($_value,$lang);
			    }
			} else {
			    $value =  $column->filterValue($value,$object->{$getter}());
			    $object->$setter($value);
			}
		}


		try {
		     if (!$pk = $object->save(false,true)) {
		         Throw New Zend_Exception("Error salvando el registro.");
		     }


		     // Recuperamos la tabla principal
		     $primaryTable = $object->getMapper()->getDbTable()->getTableName();

		     // Recorremos los campos dependientes, para proceder a salvarlos.
		     foreach($cols as $column) {

		         if (!$column->isDependant()) continue;
		         if (!$getter = $column->getGetterName($object)) continue;

		         $aModels = $object->$getter();
		         if (sizeof($aModels)>0) {

		             $model = $aModels[0];
		             $relatedIdColumn = $model->getColumnForParentTable($primaryTable);


		             // FIXME: Ya he preguntado por arriba. El campo fieldConfig de Column lo sabe WTF?
		             // "Pregunto" por existentes en BBDD
		             $relatedToPk = $aModels[0]->getMapper()->fetchList($relatedIdColumn . "='".$pk."'");

		             // Salvo relaciones nuevas, y "anoto" las que no hay que borrar
		             foreach ($aModels as $model) {
		                 if (!$model->getPrimaryKey()) {
		                     $model->{'set' .$relatedIdColumn}($pk);
		                     $model->save();
		                 } else {
		                     $notToBeDeletedRels[$model->getPrimaryKey()] = true;
		                 }
		             }

		             foreach($relatedToPk as $model) {
		                 if (!isset($notToBeDeletedRels[$model->getPrimaryKey()])) {
		                     $model->delete();
		                 }
		             }


		         }


		     }

             $data = array(
    			'error'=>false,
    			'pk'=>$object->getPrimaryKey(),
    			'message'=>'Registro modificado correctamente.'
    	    );

		} catch (Zend_Exception $exception) {

		    $data = array(
    				'error'=>true,
    				'message'=>'Error añadiendo el registro.' . $exception->getMessage()
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
	    	// Error

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
