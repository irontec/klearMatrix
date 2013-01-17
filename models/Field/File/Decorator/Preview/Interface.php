<?php
interface KlearMatrix_Model_Field_File_Decorator_Preview_Interface
{
    public function setRequest(Zend_Controller_Request_Http $request);
    public function setBinary($binary);
    public function getBinary();
}
