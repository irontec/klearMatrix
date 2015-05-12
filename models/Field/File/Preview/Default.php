<?php

class KlearMatrix_Model_Field_File_Preview_Default extends KlearMatrix_Model_Field_File_Preview_Abstract
{

    public function setFilename($filename)
    {
        $front = Zend_Controller_Front::getInstance();
        $this->_imagick = new Imagick($front->getModuleDirectory() .'/assets/bin/default.png');
        $this->_process();

    }
}
