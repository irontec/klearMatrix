<?php
abstract class KlearMatrix_Model_Field_Ghost_Abstract
{
    protected $_config;

    public function setConfig(Zend_Config $config)
    {
        $kconfig = new Klear_Model_ConfigParser;
        $kconfig->setConfig($config);

        $this->_config = $kconfig;
        return $this;
    }
}

//EOF
/*
 *
 *
    Ejemplo de un campo GHOST:

    brandFieldType:
      type: ghost
      title:
        i18n:
          es: "Tipo de Propiedad"
      source:
        class: Application_Model_GhostBrandConfType
        method: getType
        searchMethod: getSearchConditionForType
        orderMethod: getOrderField
      sortable: true

    Dado como se ha diseñado, no tiene sentido tener una clase abstracta... hubiera sido lo suyo.


    method: método invocado para el valor RAW del modelo

    seachMethod($values, $optionSearch, $object)
            >> Recibe el array de valores a buscar, la opción de buscar y el modelo - vacío-
            >> debe devolver un array de condiciones para la consulta general por ejemplo

    orderMethod: Método invocado para conseguir el campo que se insertará en "order by %campo% [desc]"
        compatible con: order by Field(nombre_campo,1,2,3,4,5)
        http://dev.mysql.com/doc/refman/5.0/en/string-functions.html#function_field


    TODO: Diseñar una nueva clase abstracta GHOST en la que se puedan implementar métodos concretos, de manera que
          la configuración sea más simple, y caiga toda la responsabilidad en la clase GHOST.




 *
 */