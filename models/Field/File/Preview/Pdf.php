<?php

class KlearMatrix_Model_Field_File_Preview_Pdf extends KlearMatrix_Model_Field_File_Preview_Abstract
{
    public function setFilename($filename)
    {
        $this->_imagick = new Imagick($filename."[0]");
        $this->_imagick->setImageFormat("png");
        $this->_process();
    }
}
