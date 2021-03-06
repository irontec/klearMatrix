<?php
class KlearMatrix_Model_ParentOptionCustomizer_AlertCount extends KlearMatrix_Model_ParentOptionCustomizer_AbstractCount
{
    /**
     * @var string sql condition
     */
    protected $_sqlCondition;

    protected $_nullIfZero = true;

    protected $_cssClass = "alertCount";

    /**
     * @param string $sqlCondition
     */
    protected function _init(Zend_Config $configuration)
    {
        if (!isset($configuration->sqlCondition) || empty($configuration->sqlCondition)) {

            throw new Exception("AlertCount requires a SQL condition");
        }

        $this->_sqlCondition = $configuration->sqlCondition;
    }

    /**
     * @return KlearMatrix_Model_ParentOptionCustomizer_Response
     */
    protected function _parseWhereCondition($where)
    {
        if (is_array($where)) {

            $where[0] = "(" . $where[0] . ") and " . $this->_sqlCondition;

        } else {

            $where = "($where) and " . $this->_sqlCondition;
        }

        return $where;
    }
}