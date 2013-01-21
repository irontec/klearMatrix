<?php

interface KlearMatrix_Model_Field_Select_Filter_Interface
{
    public function setRouteDispatcher(KlearMatrix_Model_RouteDispatcher $routeDispatcher);

    /**
     * Returns where condition for select field mapper. If no condition is needed for current object, return NULL
     * @return string|NULL
     */
    public function getCondition();
}
