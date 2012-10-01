<?php

//TO-DO: Sacar el método getWHere de ListController, hacer que este controlador herede de Zend_Controller_Action

require dirname(__FILE__) . '/ListController.php';

/**
 * Pequeño dashboard, que recorre el ficherod e configuración de klear, discrimina los klearMatric::List
 * y muestra su nombre+class, enlace y número de registros
 * @author jabi
 *
 */
class KlearMatrix_DashboardController extends KlearMatrix_ListController
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


                if (($moduleRouter->getModuleName() != "klearMatrix") || ($moduleRouter->getControllerName() != "list") ) {
                    continue;
                }

                $this->_item = $moduleRouter->getCurrentItem();

                $_mapper = \KlearMatrix_Model_Mapper_Factory::create($this->_item->getMapperName());

                $cols = $this->_item->getVisibleColumns();
                $model = $this->_item->getObjectInstance();
                $fooData = new KlearMatrix_Model_MatrixResponse();

                $where = $this->_getWhere($cols, $model, $fooData);

                $totalItems = $_mapper->countByQuery($where);

                $sectionTmp['subsects'][] = array(
                        'name' => $subsection->getName(),
                        'description' => $subsection->getDescription(),
                        'class' => $subsection->getClass(),
                        'file' => $subsection->getMainFile(),
                        'total' => $totalItems
                        );
            }

            $data['sections'][] = $sectionTmp;
        }

        $jsonResponse = new Klear_Model_DispatchResponse();
        $jsonResponse->setModule('klearMatrix');
        $jsonResponse->setPlugin('dashboard');
        $jsonResponse->addTemplate("/template/dashboard", "klearmatrixDashboard");
        $jsonResponse->addJsFile("/js/plugins/jquery.ui.form.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.template.helper.js");
        $jsonResponse->addJsFile("/js/translation/jquery.klearmatrix.translation.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.module.js");
        $jsonResponse->addJsFile("/js/plugins/jquery.klearmatrix.dashboard.js");
        $jsonResponse->addCssFile("/css/klearMatrix.css");
        $jsonResponse->setData($data);
        $jsonResponse->attachView($this->view);
    }


}
