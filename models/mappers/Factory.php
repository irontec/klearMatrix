<?php
class KlearMatrix_Model_Mapper_Factory
{
    /**
     * @param string $mapperName
     * @return KlearMatrix_Model_Mapper_Interface
     */
    public static function create($mapperName)
    {
//         if (!class_implements(KlearMatrix_Model_Mapper_Interface)) {
//             throw new \InvalidArgumentException
//                ('Specified mapper must implement KlearMatrix_Model_Mapper_Interface');
//         }

        return new $mapperName;
    }
}