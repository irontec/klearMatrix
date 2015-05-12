<?php


class KlearMatrix_Model_Field_File_Preview_Mime extends KlearMatrix_Model_Field_File_Preview_Abstract
{
    
    public static function factory($filename)
    {
        $front = Zend_Controller_Front::getInstance();
        $path = $front->getModuleDirectory() .'/assets/bin/mimetype/';
        
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $mimeTypeFile = $path . $extension . '.png';
        
        if (!file_exists($mimeTypeFile)) {
            return false;
        }

        $previewElement = new KlearMatrix_Model_Field_File_Preview_Mime();
        $previewElement->setMimeTypeSourceFile($mimeTypeFile);
        return $previewElement;
    }
    
    public function setMimeTypeSourceFile($mimeTypeFile)
    {
        $this->_imagick = new Imagick($mimeTypeFile);
        
    }
    
    public function setFilename($filename)
    {
        // $this->_imagick already set!
        $this->_process();
        return;
        
    
    }

}