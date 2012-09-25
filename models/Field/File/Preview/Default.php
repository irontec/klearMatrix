<?php

class KlearMatrix_Model_Field_File_Preview_Default implements KlearMatrix_Model_Field_File_Preview_Interface
{

    protected $_width;
    protected $_height;
    protected $_crop = false;
    protected $_binary;
    

    public function setRequest(Zend_Controller_Request_Http $request)
    {
        $this->_width = $request->getParam('width');
        $this->_height = $request->getParam('height');
        return;
    }
    
    public function setBinary($binary)
    {
        $imagick = new Imagick();
        $imagick->readimageblob($binary);
        $imagick->thumbnailimage($this->_width, $this->_height);
        $imagick->setimageformat('png32');
        $this->_binary = $imagick->getimageblob();
        return;
    }
    
    public function getBinary()
    {
        return $this->_binary;
    }

}
