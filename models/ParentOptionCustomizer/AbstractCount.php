<?php
abstract class KlearMatrix_Model_ParentOptionCustomizer_AbstractCount implements KlearMatrix_Model_Interfaces_ParentOptionCustomizer
{
    /**
     * @var KlearMatrix_Model_RouteDispatcher
     */
    protected $_mainRouter = null;

    /**
     * @var array
     */
    protected $_mainRouterOriginalParams = null;

    /**
     * @var KlearMatrix_Model_AbstractOption
     */
    protected $_option = null;

    protected $_resultWrapper = 'span';
    protected $_cssClass = '';
    protected $_nullIfZero = false;

    public function __construct(Zend_Config $configuration)
    {
        $front = Zend_Controller_Front::getInstance();
        $this->_mainRouter = $front->getRequest()->getUserParam("mainRouter");
        $this->_mainRouterOriginalParams = $this->_mainRouter ->getParams();

        if (isset($configuration->resultWrapper)) {

            $this->_resultWrapper = $configuration->resultWrapper;
        }

        if (isset($configuration->cssClass)) {

            $this->_cssClass = $configuration->cssClass;
        }

        if (isset($configuration->nullIfZero)) {

            $this->_nullIfZero = $configuration->nullIfZero;
        }

        $this->_init($configuration);
    }

    abstract protected function _init(Zend_Config $configuration);

    public function setOption (KlearMatrix_Model_AbstractOption $option)
    {
        $this->_option = $option;
    }

    /**
     * @return KlearMatrix_Model_ParentOptionCustomizer_Response
     */
    public function customize($parentModel)
    {
        $item = $this->_mainRouter->loadScreen($this->_option->getName());
        $model = $item->getObjectInstance();
        $mapper = $model->getMapper();

        //Al tratarse de un filtered screen necesita la pk del padre
        $this->_mainRouter->setParams(array("pk" => $parentModel->getPrimaryKey()));

        $listWhereCreator = new KlearMatrix_Controller_Helper_CreateListWhere;
        $where = $listWhereCreator->createListWhere(
            new KlearMatrix_Model_ColumnCollection(),
            $model,
            new KlearMatrix_Model_MatrixResponse(),
            $item
        );

        $where = $this->_parseWhereCondition($where);

        $resultCount = $mapper->countByQuery($where);
        if ($resultCount == 0 && $this->_nullIfZero == true) {

            return null;
        }

        $this->_mainRouter->setParams($this->_mainRouterOriginalParams);

        $response = new KlearMatrix_Model_ParentOptionCustomizer_Response();
        $response->setResult($mapper->countByQuery($resultCount))
                 ->setWrapper($this->_resultWrapper)
                 ->setCssClass($this->_cssClass);

        return $response;
    }

    abstract protected function _parseWhereCondition($where);
}