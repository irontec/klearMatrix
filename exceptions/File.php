<?php
class KlearMatrix_Exception_File extends \Klear_Exception_Default
{
    public function __construct($msg = 'Upload/Download file error', $code = 15000, Exception $previous = null)
    {
        return parent::__construct($msg, $code, $previous);
    }
}