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
     * @var KlearMatrix_Model_ColumnCollection
     */
    protected $_cols;

    protected $_pk;
    protected $_model;

    protected $_fileFields;

    // Prefix for files uploaded temporaly to get_sys_temp_dir
    protected $_filePrefix = 'kmatrixFSO';
    // On every successfull uploaded file, the "brother" filed uploaded before self::_hoursOld will be deleted.
    // no-cron needed by now.
    protected $_hoursOld = 24;

    public function init()
    {
        /* Initialize action controller here */
        $this->_helper->layout->disableLayout();

        $this->_helper->ContextSwitch()
            ->addActionContext('download', 'json')
            ->addActionContext('upload', 'json')
            ->initContext('json');

        $this->_mainRouter = $this->getRequest()->getUserParam("mainRouter");
        $this->_item = $this->_mainRouter->getCurrentItem();
        if (!$this->_item->hasModelFile()) {
            $errorMessage = 'modelFile must be specified in ' . $this->_item->getType() . 'configuration';
            throw new \KlearMatrix_Exception_File($errorMessage);
        }

    }

    protected function _getFileColumn()
    {
        $fileField = $this->_item->getConfigAttribute("mainColumn");
        $fileColumn = $this->_item->getColumn($fileField);

        if (!$fileColumn->isFile()) {
            throw new \KlearMatrix_Exception_File("Specified column's type must be a 'file'");
        }

        return $fileColumn;
    }

    public function uploadAction()
    {
        try {
            $column = $this->_getFileColumn();
            $colConfig = $column->getFieldConfig()->getConfig();

            $allowedExtensions = $colConfig['allowed_extensions'];
            $sizeLimit = $colConfig['size_limit'];

            //TODO: Igual habría que meter el uploader como parte de Klear? Abandonar las librerías Iron por Klear_*?
            $uploader = new Iron_QQUploader_FileUploader($allowedExtensions, $sizeLimit);

            $result = $uploader->handleUpload(
                sys_get_temp_dir(),
                false,
                $this->_filePrefix . sha1(time() . rand(1000, 10000)),
                ''
            );

            $this->_helper->log('new file uploaded (' .$result['basename'].')');

        } catch(Exception $e) {
            $this->_helper->log(
                'Error uploading File [' . $e->getCode() . '] (' . $e->getMessage() . ')',
                Zend_Log::ERR
            );

            throw new \KlearMatrix_Exception_File($e->getMessage(), $e->getCode());
        }

        $tempFSystemNS = new Zend_Session_Namespace('File_Controller');
        $tempFSystemNS->{$result['filename']} = array(
                                                    'path'=>$result['path'],
                                                    'basename' => $result['basename']);
        $this->_clearOldFiles();
        $this->view->success = true;
        $this->view->code = $result['filename'];
    }

    protected function _clearOldFiles()
    {

        $files = glob(sys_get_temp_dir() . '/' . $this->_filePrefix . '*');
        $secsLimit = time() - ($this->_hoursOld * 3600);

        foreach ($files as $file) {

            if (!is_file($file)) {
                // WTF?!?!?! symlink? dir? some maderfoker in the house?
                $this->_helper->log(
                    'KlearMatrix::FSO NOT A FILE TO BE DELETED! something nasty!! ['.$file.']',
                    Zend_Log::ALERT
                );
                continue;
            }

            if (filemtime($file) < $secsLimit) {
                $this->_helper->log('KlearMatrix::FSO Deleting OLD file ['.basename($file).']');
                unlink($file);
            }
        }
    }

    public function forceDownloadAction()
    {
        $this->getRequest()->setParam("download", true);
        return $this->downloadAction();
    }

    /**
     *
     * //TODO: Documentar esta acción, el uso de forceDownload está claro, pero esta acción para que sirve??
     * @throws Zend_Exception
     * @throws Zend_Controller_Action_Exception
     */
    public function downloadAction()
    {
        try {
            $this->_loadModel();

            if (!$this->_model) {
                $this->_helper->log(
                    'Model not found for '. $this->_item->getMapperName() . ' >> PK(' .$this->_pk .')',
                    Zend_Log::ERR
                );
                throw new Zend_Exception("No se encuentra la columna solicitada.");
            }

            $this->_setFileFields();

            if ((bool)$this->_request->getParam("download")) {

                $nameGetter = 'get' . $this->_fileFields['baseNameName'];

                $this->_helper->log('Sending file to Client: ('.$this->_model->{$nameGetter}().')');

                $file = $this->_getFilePath();
                $isRaw = false;

                if (!file_exists($file)) {

                    //SOAP compatibility mode
                    $file = $this->_getBinary();
                    $isRaw = true;
                }

                $this->_helper->sendFileToClient(
                    $file,
                    array('filename' => $this->_model->{$nameGetter}()),
                    $isRaw
                );

                $response = Zend_Controller_Front::getInstance()->getResponse();
                $response->clearHeaders();

                return;
            }

            $nameGetter = 'get' . $fileFields['baseNameName'];
            $sizeGetter = 'get' . $fileFields['sizeName'];
            $mimeGetter = 'get' . $fileFields['mimeName'];

            $data = array(
                    'pk'=>$this->_pk,
                    'message'=> $this->_model->{$nameGetter}().'<br />('.$this->_model->{$sizeGetter}().')' ,
                    'buttons'=>array(
                            $this->_helper->translate('Cancel') => array(
                                    'recall'=>false,
                            ),
                            $this->_helper->translate('Download') => array(
                                    'recall'=>true,
                                    'external'=>true,
                                    'params'=>array(
                                            "download"=>true
                                    )
                            )
                    )

            );
        } catch(Exception $e) {

            if (!$this->_request->isXmlHttpRequest()) {

                $this->_helper->log('Error Downloading; request not from XHR.', Zend_Log::ERR);

                throw new Zend_Controller_Action_Exception('File not found.', 404);
                return;
            }

            $this->_helper->log('Error Downloading; ('.$e->getMessage().')', Zend_Log::ERR);

            $data = array(
                    'message'=>sprintf($this->view->translate('Error preparing download.<br />(%s)'), $e->getMessage()),
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

    /**
     * @deprecated Sustituir por forceDownloadAction, que es bastante más explicito
     * //TODO: Hacer desaparecer esto.
     */
    public function forcedwAction()
    {
        return $this->forceDownloadAction();
    }

    /**
     * TODO: Imágen por defecto si el modelo no existe
     * TODO: Default preview de default
     * TODO: Sistema de cacheo
     */
    public function previewAction()
    {

        try {
            $this->_loadModel();

            if (!$this->_model) {
                Throw new Exception("file not exists");
            }

            $this->_setFileFields();

            $typeGetter = 'get' . $this->_fileFields['mimeName'];
            $nameGetter = 'get' . $this->_fileFields['baseNameName'];

            $mimeType = $this->_model->{$typeGetter}();
            $filename = $this->_model->{$nameGetter}();

            switch (true) {
                case preg_match('/^image*[jpg|gif|jpeg|png|bmp]/i', $mimeType):
                    $previewElement = new KlearMatrix_Model_Field_File_Preview_Image();
                    $previewElement->setRequest($this->getRequest());
                    $previewElement->setBinary($this->_getBinary());
                    break;
                default:
                    Throw new Exception("file type not valid");
                    break;
            }

        } catch(Exception $e) {

            $mimeType = 'image/png';
            $filename = 'default.png';

            $front = $this->getFrontController();
            $imageBlob = file_get_contents($front->getModuleDirectory() .'/assets/bin/default.svg');

            $previewElement = new KlearMatrix_Model_Field_File_Preview_Default();
            $previewElement->setRequest($this->getRequest());
            $previewElement->setBinary($imageBlob);
        }


        $this->_helper->log('Sending file to Client: ('.$filename.')');
        $this->_helper->sendFileToClient(
            $previewElement->getBinary(),
            array(
                'filename' => $filename,
                'type' => $mimeType,
                'disposition' => 'inline'
            ),
            true
        );

        $response = Zend_Controller_Front::getInstance()->getResponse();
        $response->clearHeaders();

        return;
    }

    /**
     * Cargar $this->_model
     * @throws Zend_Exception
     */
    protected function _loadModel()
    {
        $mapperName = $this->_item->getMapperName();
        $mapper = new $mapperName;
        $this->_pk = $this->_mainRouter->getParam("pk");
        $this->_model = $mapper->find($this->_pk);

        return;
    }

    /**
     * Recuperar el binary del file
     */
    protected function _getBinary()
    {
        $column = $this->_getFileColumn();
        $fetchGetter = $column->getFieldConfig()->getFetchMethod($this->_item->getConfigAttribute("mainColumn"));
        return $this->_model->{$fetchGetter}()->getBinary();
    }

    /**
     * Recuperar la ruta del fichero
     */
    protected function _getFilePath()
    {
        $column = $this->_getFileColumn();
        $fetchGetter = $column->getFieldConfig()->getFetchMethod($this->_item->getConfigAttribute("mainColumn"));
        return $this->_model->{$fetchGetter}()->getFilePath();
    }

    protected function _setFileFields()
    {
        $fieldSpecsGetter = "get" . $this->_item->getConfigAttribute("mainColumn") . "Specs";
        $this->_fileFields = $this->_model->{$fieldSpecsGetter}();
        return;
    }

}
