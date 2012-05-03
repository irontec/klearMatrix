<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/

class KlearMatrix_Model_Field_Ghost extends KlearMatrix_Model_Field_Abstract
{

    public function getCustomGetterName()
    {
        return 'getGhostValue';
    }

}