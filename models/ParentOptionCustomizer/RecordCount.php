<?php
class KlearMatrix_Model_ParentOptionCustomizer_RecordCount extends KlearMatrix_Model_ParentOptionCustomizer_AbstractCount
{
    protected $_cssClass = "recordCount";

    /**
     * @param string $sqlCondition
     */
    protected function _init(Zend_Config $configuration)
    {
    }

    /**
     * @return KlearMatrix_Model_ParentOptionCustomizer_Response
     */
    protected function _parseWhereCondition($where)
    {
        return $where;
    }
}