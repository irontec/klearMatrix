<?php

class KlearMatrix_Bootstrap extends Zend_Application_Module_Bootstrap
{

    protected function _initSyslog()
    {
        $writer = new \Zend_Log_Writer_Syslog(
            array(
                'application' => 'klearMatrix',
                'facility' => LOG_LOCAL6,
            )
        );

        Zend_Registry::set('syslog', new \Zend_Log($writer));
    }

    protected function _initAutoload()
    {
        $autoloader = new Zend_Application_Module_Autoloader(
            array(
                'namespace' => 'KlearMatrix',
                'basePath'  => __DIR__,
            )
        );

        $autoloader->addResourceType('actionhelpers', 'controllers/helpers/', 'Controller_Helper');
        $autoloader->addResourceType('exceptions', 'exceptions/', 'Exception');

        Zend_Controller_Action_HelperBroker::addPath(
            __DIR__ . '/controllers/helpers',
            'KlearMatrix_Controller_Helper_'
        );

        return $autoloader;
    }

}
