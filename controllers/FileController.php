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
            $dwColumn = $this->_getFileColumn();

            $mapperName = $this->_item->getMapperName();

            $mapper = new $mapperName;

            $this->_pk = $this->_mainRouter->getParam("pk");

            $this->_helper->log('Download stated for file in '. $mapperName. ' >> PK('.$this->_pk.')');

            if (!$this->_model = $mapper->find($this->_pk)) {

                $this->_helper->log('Model not found for '. $mapperName. ' >> PK('.$this->_pk.')', Zend_Log::ERR);

                throw new Zend_Exception("No se encuentra la columna solicitada.");
            }

            $downloadField = $this->_item->getConfigAttribute("mainColumn");
            $fieldSpecsGetter = "get" . $downloadField . "Specs";
            $fileFields = $this->_model->{$fieldSpecsGetter}();

            if ((bool)$this->_request->getParam("download")) {

                $fetchGetter = $dwColumn->getFieldConfig()->getFetchMethod($downloadField);
                $nameGetter = 'get' . $fileFields['baseNameName'];

                $this->_helper->log('Sending file to Client: ('.$this->_model->{$nameGetter}().')');
                $this->_helper->sendFileToClient(
                    $this->_model->{$fetchGetter}()->getBinary(),
                    array('filename' => $this->_model->{$nameGetter}()),
                    true
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
                            $this->view->translate('Cancel') => array(
                                    'recall'=>false,
                            ),
                            $this->view->translate('Download') => array(
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


}
