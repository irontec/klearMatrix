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


        $keyGenerator = new \Klear_Model_CacheKeyGenerator('dashboardKlearMatrix');
        $cacheKey = $keyGenerator->getKey();

        $cache = $cacheManager->getCache('klearmatrixDashboard');
        $cache->start($cacheKey);

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
        $sectionsBlackList = array();
        if ($this->_item->getSectionsBlackList()) {
            $sectionsBlackList = $this->_item->getSectionsBlackList();
            $sectionsBlackList = $sectionsBlackList->toArray();
        }

        $menuConfig = Zend_Controller_Front::getInstance()
                        ->getParam('bootstrap')
                        ->getResource('modules')
                        ->offsetGet('klear')
                        ->getOption('menu');

        $data['sections'] = array();

        foreach ($menuConfig as $section) {

            $sectionTmp = $this->parseSection(
                $section,
                $sectionsBlackList
            );

            if (sizeof($sectionTmp['subsects'])>0) {
                $data['sections'][] = $sectionTmp;
            }
        }

        $jsonResponse = KlearMatrix_Model_DispatchResponseFactory::build();
        $jsonResponse->setPlugin('dashboard');
        $jsonResponse->addTemplate("/template/dashboard", "klearmatrixDashboard");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.dashboard.js");
        $jsonResponse->setData($data);
        $jsonResponse->attachView($this->view);
    }

    protected function _calculateForKlearMatrixList($moduleRouter, $subsection, $countRows = true)
    {
        $totalItems = null;
        if ($countRows) {

            $_item = $moduleRouter->getCurrentItem();
            $entity = $_item->getEntityClassName();
            $dataGateway = \Zend_Registry::get('data_gateway');
            $model = null;
            $fakeData = new KlearMatrix_Model_MatrixResponse();

            /**
             * El primer paramétro de createListWhere solamente se usa para construir
             * la condición para filtrar resultados en ListControllers
             */
            $where = $this->_helper->createListWhere(new KlearMatrix_Model_ColumnCollection(), $model, $fakeData, $_item);
            $totalItems = $dataGateway->countBy($entity, $where);
        }

        return array(
                'name' => $subsection->getName(),
                'class' => $subsection->getClass(),
                'file' => $subsection->getMainFile(),
                'subtitle' => $totalItems
        );
    }

    /**
     * @param $section
     * @param $sectionsBlackList
     * @return array
     */
    protected function parseSection($section, $sectionsBlackList): array
    {
        $sectionTmp = array(
            'name' => $section->getName(),
            'class' => $section->getClass(),
            'meta' => $section->getMeta(),
            'subsects' => array()
        );

        foreach ($section->getSubsections() as $subsection) {

            if ($subsection->hasSubsections()) {
                $break = 1;
                $sectionTmp['subsects'][] = $this->parseSection(
                    $subsection,
                    $sectionsBlackList
                );

                continue;
            }

            $file = $subsection->getMainFile();

            $sectionConfig = new Klear_Model_SectionConfig;
            $sectionConfig->setFile($file);
            if (!$sectionConfig->isValid()) {
                continue;
            }

            if (in_array($file, $sectionsBlackList)) {
                continue;
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

            $countRows =
                ($moduleRouter->getModuleName() == "klearMatrix")
                && ($moduleRouter->getControllerName() == "list")
                && $subsection->shouldCountRows();

            $sectionTmp['subsects'][] = $this->_calculateForKlearMatrixList(
                $moduleRouter,
                $subsection,
                $countRows
            );
        }

        return $sectionTmp;
    }
}
