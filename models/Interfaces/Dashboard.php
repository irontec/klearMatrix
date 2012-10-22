<?php

interface KlearMatrix_Model_Interfaces_Dashboard
{

    public function setConfig(Zend_Config $config);
    public function getName();
    public function getClass();
    public function getFile();
    public function getSubtitle();

}