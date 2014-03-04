<?php

interface KlearMatrix_Model_Field_Select_Filter_Interface
{
    public function setRouteDispatcher(KlearMatrix_Model_RouteDispatcher $routeDispatcher);

    /**
     * Returns where condition for select field mapper. If no condition is needed for current object, return NULL
     * Returns array with keys to exclude for select field inline. If no condition is needed for current object, return NULL or empty array
     * @return string|array|NULL
     */
    public function getCondition();
}
