<?php

abstract class KlearMatrix_Model_Field_File_Preview_Abstract
{
    protected $_width;
    protected $_height;
    protected $_crop = false;
    protected $_imagick;

    

    static function factory($fileName, $mimeType)
    {
        
        if (preg_match('/^image/i', $mimeType)) {
            return new KlearMatrix_Model_Field_File_Preview_Image();
        }
    
        if (preg_match('/^application.*pdf/i', $mimeType)) {
            return new KlearMatrix_Model_Field_File_Preview_Pdf();
        }
        
        if ($previewMime = KlearMatrix_Model_Field_File_Preview_Mime::factory($fileName)) {
            return $previewMime;
        }
        
        return new KlearMatrix_Model_Field_File_Preview_Default();
    }
    
    public function setRequest(Zend_Controller_Request_Http $request)
    {
        $this->_width = $request->getParam('width', '100');
        $this->_height = $request->getParam('height', '100');
        if ($request->getParam('crop', 'false') == 'true') {
            $this->_crop = true;
        }
        return;
    }

    protected function _process() {
        
        
        \Iron_Utils_PngFix::process($this->_imagick);
        
        if ($this->_crop === true) {
            $this->_imagick->cropthumbnailimage($this->_width, $this->_height);
        } else {
            $this->_imagick->thumbnailimage($this->_width, $this->_height, true);
        }

    }
    
    public function setFilename($filename)
    {
        $this->_imagick = new Imagick($filename);
        $this->_process();
    }
    public function setBinary($binary)
    {
        $this->_imagick = new Imagick();
        $this->_imagick->readimageblob($binary);
        $this->_process();
    }
    
    public function getBinary() {
        return $this->_imagick->getimageblob();
    }
    
    public function getMimeType()
    {
        return $this->_imagick->getimagemimetype();
    }
}
