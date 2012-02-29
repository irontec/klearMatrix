<?php

/**
 * @author jabi
 *
 */
class KlearMatrix_FileController extends Zend_Controller_Action
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


    /**
     * @var KlearMatrix_Model_ColumnWrapper
     */
    protected $_cols;

    protected $_pk;
    protected $_model;


    public function init()
    {
        /* Initialize action controller here */
    	$this->_helper->layout->disableLayout();

    	$this->_helper->ContextSwitch()
    		->addActionContext('download', 'json')
    		->addActionContext('upload', 'json')
    		->initContext('json');

    	$this->_mainRouter = $this->getRequest()->getParam("mainRouter");
    	$this->_item = $this->_mainRouter->getCurrentItem();

    }

    protected function _getFileColumn() {

        $fileField = $this->_item->getConfigAttribute("mainColumn");

        $fileColumn = $this->_item->getColumn($fileField);

        if (!$fileColumn->isFile()) {
            Throw new Zend_Exception("La columna especificada no es de tipo fichero.");
        }

        return $fileColumn;

    }


    public function uploadAction() {

        try {
            $column = $this->_getFileColumn();

            $colConfig = $column->getFieldConfig()->getConfig();

            $allowedExtensions = explode(',', $colConfig['allowed_extensions']);
            $sizeLimit = $colConfig['size_limit'];

            $uploader = new Iron_QQUploader_FileUploader($allowedExtensions,
                                                                    $sizeLimit);

            $result = $uploader->handleUpload(sys_get_temp_dir(), false, sha1(time().rand(1000,10000)), '');

        } catch(Exception $e) {

            $this->view->error = true;
            $this->view->error_number = $e->getCode();
            $this->view->error_msg = $e->getMessage();
            return;

        }

        $tempFSystemNS = new Zend_Session_Namespace('File_Controller');
        $tempFSystemNS->{$result['filename']} = array(
                                                    'path'=>$result['path'],
                                                    'basename' => $result['basename']);

        $this->view->success = true;
        $this->view->code = $result['filename'];

    }

    public function downloadAction()
    {


        try {

            $dwColumn = $this->_getFileColumn();

            $mapperName = $this->_item->getMapperName();

            $mapper = new $mapperName;

            $this->_pk = $this->_mainRouter->getParam("pk");
            if (!$this->_model = $mapper->find($this->_pk)) {
                Throw new Zend_Exception("No se encuentra la columna solicitada.");
            }

            $downloadField = $this->_item->getConfigAttribute("mainColumn");
            $fieldSpecsGetter = "get" . $downloadField . "Specs";
            $fileFields = $this->_model->{$fieldSpecsGetter}();



	        if ((bool)$this->_request->getParam("download")) {

	            $fetchGetter = $dwColumn->getFieldConfig()->getFetchMethod($downloadField);
	            $nameGetter = 'get' . $fileFields['baseNameName'];

	            
	            
	            $this->_helper->sendFileToClient(
	                                $this->_model->{$fetchGetter}()->getBinary(),
	                                array('filename'=>$this->_model->{$nameGetter}()),
	                                true);
	            exit;
	        }


	        $nameGetter = 'get' . $fileFields['baseNameName'];
	        $sizeGetter = 'get' . $fileFields['sizeName'];
	        $mimeGetter = 'get' . $fileFields['mimeName'];

	        $data = array(
	                'pk'=>$this->_pk,
	                'message'=> $this->_model->{$nameGetter}().'<br />('.$this->_model->{$sizeGetter}().')' ,
	                'buttons'=>array(
	                        'Cancelar' => array(
	                                'recall'=>false,
	                        ),
	                        'Descargar' => array(
	                                'recall'=>true,
	                                'external'=>true,
	                                'params'=>array(
	                                        "download"=>true
	                                )
	                        )
	                )

	        );


	    } catch(Exception $e) {

	        $data = array(
	                'message'=>'Error preparando la descarga.<br />('.$e->getMessage().')',
	                'buttons'=>array(
	                        'Aceptar' => array(
	                                'recall'=>false,
	                        )
	                )
	        );

	    }


        $jsonResponse = new Klear_Model_DispatchResponse();
        $jsonResponse->setModule('klearMatrix');
        $jsonResponse->setPlugin('klearMatrixGenericDialog');
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.genericdialog.js");
        $jsonResponse->setData($data);
        $jsonResponse->attachView($this->view);


	}

}
