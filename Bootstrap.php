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

}
