<?php

class KlearMatrix_Model_Field_File_Preview_Image implements KlearMatrix_Model_Field_File_Preview_Interface
{

    protected $_width;
    protected $_height;
    protected $_crop = false;
    protected $_binary;
    

    public function setRequest(Zend_Controller_Request_Http $request)
    {
        $this->_width = $request->getParam('width', '200');
        $this->_height = $request->getParam('height', '200');
        if ($request->getParam('crop') == 'true') {
            $this->_crop = true;
        } else {
            $this->_crop = false;
        }
        return;
    }
    
    public function setBinary($binary)
    {
        $imagick = new Imagick();
        $imagick->readimageblob($binary);
        if ($this->_crop === true) {
            $imagick->cropthumbnailimage($this->_width, $this->_height);
        } else {
            $imagick->thumbnailimage($this->_width, $this->_height);
        }
        $this->_binary = $imagick->getimageblob();
        return;
    }
    
    public function getBinary()
    {
        return $this->_binary;
    }

}
