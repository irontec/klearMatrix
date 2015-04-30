<?php
class KlearMatrix_Plugin_Init extends Zend_Controller_Plugin_Abstract
{
    /**
     * @var Zend_Controller_Front
     */
    protected $_front;

    /**
     * @var Klear_Bootstrap
     */
    protected $_bootstrap;

    /**
     * @var Zend_Config
     */
    protected $_config;


    /**
     * Este método que se ejecuta una vez se ha matcheado la ruta adecuada
     * (non-PHPdoc)
     * @see Zend_Controller_Plugin_Abstract::routeShutdown()
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        if (!preg_match("/^klear/", $request->getModuleName())) {
            return;
        }

        $this->_initPlugin();
        $this->_initCacheManager();
    }

    /**
     * Inicia los atributos utilizados en el plugin
     */
    public function _initPlugin()
    {
        $this->_front = Zend_Controller_Front::getInstance();
        $this->_bootstrap = $this->_front
        ->getParam('bootstrap')
        ->getResource('modules')
        ->offsetGet('klear');
    }

    protected function _initCacheManager()
    {
        $bootstrap = Zend_Controller_Front::getInstance()
                     ->getParam('bootstrap');
        $cacheManager = $bootstrap->getResource('cachemanager');
        if (!$cacheManager) {
            $cacheManager = new Zend_Cache_Manager();
            $bootstrap->getContainer()->cachemanager = $cacheManager;
        }
        //Default frontend config
        $frontend = array(
            'name' => 'Page',
            'options' => array(
                'default_options' => array(
                        'cache_with_get_variables' => true,
                        'cache_with_session_variables' => true,
                        'cache_with_cookie_variables' => true,
                        'make_id_with_session_variables' => false,
                        'make_id_with_cookie_variables' => false
                ),
                'lifetime' => 300,
                'memorize_headers' => array(
                    'Content-Type'
                )
            )
        );

        //Default backend config
        $backend = array(
            'name' => 'Black_Hole'
        );

        if (!$cacheManager->hasCacheTemplate('klearmatrixDashboard')) {
            $cache = array(
                    'frontend' => $frontend,
                    'backend' => $backend
            );

            $cacheManager->setCacheTemplate('klearmatrixDashboard', $cache);
        } else {
            $bootstrapConfig = $cacheManager->getCacheTemplate('klearmatrixDashboard');


            if (isset($bootstrapConfig['frontend']['options']['default_options'])) {

                $mergedDefaultOptions = false;
                if (isset($bootstrapConfig['frontend']['options']['default_options'])) {
                    $mergedDefaultOptions = array_merge(
                        $frontend['options']['default_options'],
                        $bootstrapConfig['frontend']['options']['default_options']
                    );
                }

                $frontend['options'] = array_merge(
                    $frontend['options'],
                    $bootstrapConfig['frontend']['options']
                );

                if ($mergedDefaultOptions) {
                    $frontend['options']['default_options'] = $mergedDefaultOptions;
                }

            }

            if (isset($bootstrapConfig['backend']['name'])) {
                $backend = $bootstrapConfig['backend'];
            } else {
                if (isset($bootstrapConfig['backend'])) {
                    throw new \Exception('No backend name configured for klearMatrixDashBoard (but some other config found).');
                }
            }

            $cacheManager->setTemplateOptions(
                'klearmatrixDashboard',
                array(
                    'frontend' => $frontend,
                    'backend' => $backend
                )
            );
        }
    }

}
