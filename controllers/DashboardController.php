<?php

/**
 * Pequeño dashboard, que recorre el ficherod e configuración de klear, discrimina los klearMatrix::List
 * y muestra su nombre+class, enlace y número de registros
 *
 * @author jabi
 *
 */
class KlearMatrix_DashboardController extends Zend_Controller_Action
{
    /**
     * Route Dispatcher desde klear/index/dispatch
     * @var KlearMatrix_Model_RouteDispatcher
     */
    protected $_mainRouter;

    /**
     * Screen|Dialog
     * @var KlearMatrix_Model_ResponseItem
     */
    protected $_item;

    public function init()
    {
        $cacheManager = $this->getInvokeArg('bootstrap')->getResource('cachemanager');
        $cache = $cacheManager->getCache('klearmatrixDashboard');
        $cache->start();

        /* Initialize action controller here */
        $this->_helper->layout->disableLayout();

        $this->_helper->ContextSwitch()
            ->addActionContext('index', 'json')
            ->initContext('json');

        $this->_mainRouter = $this->getRequest()->getUserParam("mainRouter");
        $this->_item = $this->_mainRouter->getCurrentItem();
    }


    public function indexAction()
    {
        $data = array();
        $data['title'] = $this->_item->getTitle();

        if ($this->_item->getUseExplain()) {
            $data['title'] .= ' ' . $this->_helper->translate('(Aproximated values)');
        }

        $menuConfig = Zend_Controller_Front::getInstance()
                        ->getParam('bootstrap')
                        ->getResource('modules')
                        ->offsetGet('klear')
                        ->getOption('menu');

        $data['sections'] = array();

        foreach ($menuConfig as $section) {
            $sectionTmp = array(
                    'name' => $section->getName(),
                    'subsects' => array()
            );

            foreach ($section as $subsection) {

                $file = $subsection->getMainFile();

                $sectionConfig = new Klear_Model_SectionConfig;
                $sectionConfig->setFile($file);
                if (!$sectionConfig->isValid()) {
                    continue;
                    return;
                }

                // Nos devuelve el configurador del módulo concreto instanciado.
                $moduleConfig = $sectionConfig->factoryModuleConfig();
                $moduleRouter = $moduleConfig->buildRouterConfig();
                $moduleRouter->resolveDispatch();

                if ($moduleRouter->getCurrentItem()->getRawConfigAttribute("dashboard->class")) {

                    $dashElementClassName = $moduleRouter->getCurrentItem()->getRawConfigAttribute("dashboard->class");
                    $dashSection = new $dashElementClassName;
                    $dashSection->setConfig($moduleRouter->getCurrentItem()->getRawConfigAttribute("dashboard"));
                    $dashSection->setItem($moduleRouter->getCurrentItem());
                    $sectionTmp['subsects'][] = array(
                            'name' => $dashSection->getName(),
                            'class' => $dashSection->getClass(),
                            'file' => $dashSection->getFile(),
                            'subtitle' => $dashSection->getSubTitle()
                    );

                    continue;
                }

                /*
                 * Para KlearMatrix List, se calcula automáticamente.
                 */
                if (($moduleRouter->getModuleName() == "klearMatrix") &&
                        ($moduleRouter->getControllerName() == "list") ) {
                    $sectionTmp['subsects'][] = $this->_calculateForKlearMatrixList($moduleRouter, $subsection);
                    continue;
                }
            }

            $data['sections'][] = $sectionTmp;
        }

        $jsonResponse = KlearMatrix_Model_DispatchResponseFactory::build();
        $jsonResponse->setPlugin('dashboard');
        $jsonResponse->addTemplate("/template/dashboard", "klearmatrixDashboard");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.dashboard.js");
        $jsonResponse->setData($data);
        $jsonResponse->attachView($this->view);
    }

    protected function _calculateForKlearMatrixList($moduleRouter, $subsection)
    {
        $_item = $moduleRouter->getCurrentItem();

        $_mapper = \KlearMatrix_Model_Mapper_Factory::create($_item->getMapperName());

        //$cols = $_item->getVisibleColumns();
        $model = $_item->getObjectInstance();
        $fakeData = new KlearMatrix_Model_MatrixResponse();

        /**
         * El primer paramétro de createListWhere solamente se usa para construir
         * la condición para filtrar resultados en ListControllers
         */
        $where = $this->_helper->createListWhere(new KlearMatrix_Model_ColumnCollection(), $model, $fakeData, $_item);

        if (!$where) {
            $totalItems = $_mapper->countAllRows($this->_item->getUseExplain());
        } else {
            $totalItems = $_mapper->countByQuery($where);
        }

        return array(
                'name' => $subsection->getName(),
                'class' => $subsection->getClass(),
                'file' => $subsection->getMainFile(),
                'subtitle' => $totalItems
        );

    }
}
