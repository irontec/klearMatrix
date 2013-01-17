<?php
class KlearMatrix_Model_Field_File_Decorator_Preview extends KlearMatrix_Model_Field_DecoratorAbstract
{
    protected $_binary;
    protected $_fileFields;

    protected function _init()
    {

    }

    public function run()
    {
        try {

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
                    $previewElement = new KlearMatrix_Model_Field_File_Decorator_Preview_Image();
                    $previewElement->setRequest($this->_request);
                    $previewElement->setBinary($this->_getBinary());
                    break;
                default:
                    Throw new Exception("file type not valid");
                    break;
            }

        } catch(Exception $e) {

            $mimeType = 'image/png';
            $filename = 'default.png';

            $imageBlob = file_get_contents($this->_front->getModuleDirectory() .'/assets/bin/default.svg');

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

        $response = $this->_front->getResponse();
        $response->clearHeaders();

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


    protected function _getFileColumn()
    {
        $fileField = $this->_item->getConfigAttribute("mainColumn");
        $fileColumn = $this->_item->getColumn($fileField);

        if (!$fileColumn->isFile()) {
            throw new \KlearMatrix_Exception_File("Specified column's type must be a 'file'");
        }

        return $fileColumn;
    }

    protected function _setFileFields()
    {
        $fieldSpecsGetter = "get" . $this->_item->getConfigAttribute("mainColumn") . "Specs";
        $this->_fileFields = $this->_model->{$fieldSpecsGetter}();
        return;
    }

}