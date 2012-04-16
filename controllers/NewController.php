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

       $model = $this->_item->getObjectInstance();
       // Cargamos las columnas visibles, ignorando blacklist
       $cols = $this->_item->getVisibleColumnWrapper();
       $hasDependant = false;
 
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
        
        
            if (!$setter = $column->getSetterName($model)) continue;
            if (!$getter = $column->getGetterName($model)) continue;
            
            switch(true) {
                case ($column->isMultilang()):
                    foreach($value as $lang => $_value) {
                        $_value =  $column->filterValue($_value,$model->{$getter}($lang));
                        $model->$setter($_value,$lang);
                    }
                    break;
            
                case ($column->isDependant()):
                    $value = $column->filterValue($value,$model->{$getter}());
                    $model->$setter($value,true);
                    $hasDependant = true;
                    break;
            
                case ($column->isFile()):
                    
                    $value = $column->filterValue($value,$model->{$getter}());
                    if ($value !== false) {
                        $model->$setter($value['path'],$value['basename']);
                    }
            
                    break;
            
            
                default:
                    $model->$setter($value);
            
            }
            
        }

        if ($this->_item->hasForcedValues()) {

            foreach($this->_item->getForcedValues() as $field => $value) {
            
                try {
                    $varName = $model->columnNameToVar($field);
                    $model->{'set' . $varName}($value);
                    
                } catch (Exception $e) {
                    // Nothing to do... condition not found in model... :S
                    // Debemos morir??
                }
            }
            
        }
        
        // Si la pantalla esta filtrada, debemos setearla en la "nueva"
        if ($this->_item->isFilteredScreen()) {

            $filteredField = $this->_item->getFilteredField();
            
            $filteredValue = $this->_mainRouter->getParam($filteredField);
            // TODO: Para el screename del parent, recuperar mapper, fetchById, y comprobar que existe el parámetro recibido.
            
            $filterFieldSetter = 'set' . $model->columnNameToVar($filteredField);
            $model->{$filterFieldSetter}($filteredValue);
        } 
            
                
        try {
             $model->save(false,$hasDependant);
             $data = array(
    			'error'=>false,
    			'pk'=>$model->getPrimaryKey(),
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

	    // La pantalla "nuevo" tiene filtro? cae de otro listado?
	    if ($this->_item->isFilteredScreen()) {
	        
	        // Informamos a la respuesta de que campo es el "padre"
	        $data->setParentItem($this->_item->getFilteredField());
	        
	        // A partir del nombre de pantalla (de nuestro .yaml principal...
    	    if ($parentScreenName = $this->getRequest()->getPost("parentScreen")) {
    	    
    	        // Instanciamos pantalla
                $parentScreen = new KlearMatrix_Model_Screen;
                $parentScreen->setRouteDispatcher($this->_mainRouter);
                $parentScreen->setConfig($this->_mainRouter->getConfig()->getScreenConfig($parentScreenName));
                $parentMapperName = $parentScreen->getMapperName();
        
                $parentColWrapper = $parentScreen->getVisibleColumnWrapper();
                $defaultParentCol = $parentColWrapper->getDefaultCol();
        
                // Recuperamos mapper, para recuperar datos principales (default value)
                $parentMapper = new $parentMapperName;
                $parentId = $this->_mainRouter->getParam('parentId');
                $parentData = $parentMapper->find($parentId);
                
                
                $getter = 'get' . $parentData->columnNameToVar($defaultParentCol->getDbName() );
                
                // Se añaden los datos a la respuesta
                // Se recogerán en el new, y se mostrará información por pantalla
                $data->setParentIden($parentData->$getter());
                $data->setParentId($parentId);
                $data->setParentScreen($parentScreenName);
    	    }
    	    
	    }
	    
	    Zend_Json::$useBuiltinEncoderDecoder = true;

	    $jsonResponse = new Klear_Model_DispatchResponse();
	    $jsonResponse->setModule('klearMatrix');
	    $jsonResponse->setPlugin('klearMatrixNew');
	    $jsonResponse->addTemplate("/template/new/type/" . $this->_item->getType(),"klearmatrixNew");
	    $jsonResponse->addTemplateArray($cols->getTypesTemplateArray("/template/field/type/","klearMatrixFields"));
	    $jsonResponse->addTemplate($cols->getMultiLangTemplateArray("/template/",'field'),"klearmatrixMultiLangField");
	    
	    $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.template.helper.js");
	    $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.module.js");
	    $jsonResponse->addJsFile("/js/scripts/2.5.3-crypto-md5.js");
	    $jsonResponse->addJsFile("/js/plugins/jquery.autoresize.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.h5validate.js");
	    $jsonResponse->addJsFile("/js/plugins/jquery.ui.form.js");
	    $jsonResponse->addJsArray($cols->getColsJsArray());
	    $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.edit.js"); // klearmatrix.new hereda de klearmatrix.edit
	    $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.new.js");
	    $jsonResponse->addCssFile("/css/klearMatrixNew.css");
	    $jsonResponse->addCssArray($cols->getColsCssArray());
	    $jsonResponse->setData($data->toArray());
	    $jsonResponse->attachView($this->view);

	}

}
