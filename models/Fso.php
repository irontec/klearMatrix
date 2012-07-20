<?php
/***
 * File system object
 * FIXME: Hacer funcionar correctamente fetch y remove.
*/
class KlearMatrix_Model_Fso
{
    const CLASS_ATTR_SEPARATOR = '.';

    protected $_model;
    protected $_modelSpecs;
    protected $_basePath = '';

    protected $_srcFile;
    protected $_size = null;
    protected $_mimeType;
    protected $_baseName = '';

    protected $_mustFlush = false;

    public function __construct($model, $specs)
    {
        $storagePath = $this->_getLocalStorage($this->_getConfig());

        $this->_model = $model;
        $this->_modelSpecs = $specs;

        $modelClassName = $this->_getModelClassName();
        $modelAttrPath = strtolower($modelClassName . self::CLASS_ATTR_SEPARATOR . $specs['basePath']);
        $this->_basePath = $storagePath . $modelAttrPath;
    }

    protected function _getConfig()
    {
        $bootstrap = \Zend_Controller_Front::getInstance()->getParam('bootstrap');

        if (is_null($bootstrap)) {

            $conf = new \Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);

        } else {

            $conf = (Object) $bootstrap->getOptions();
        }

        return $conf;
    }

    protected function _getLocalStorage($conf)
    {
        if (isset($conf->localStoragePath)) {

            $storagePath = $conf->localStoragePath;
            if (substr($storagePath, -1) != DIRECTORY_SEPARATOR) {
                $storagePath .= DIRECTORY_SEPARATOR;
            }

            return $storagePath;
        }
        return APPLICATION_PATH . '/../storage/';
    }

    protected function _getModelClassName()
    {
        return str_replace('\\', '_', get_class($this->_model));
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->_size;
    }

    /**
     * @return true
     */
    public function getBaseName()
    {
        return $this->_baseName;
    }

    /**
     * @var string
     */
    public function getMimeType()
    {
        return $this->_mimeType;
    }

    /**
     * Prepara el módelo para poder guardar el fichero pasado como parámetro.
     * No guarda el fichero, lo prepara para guardarlo al llamar a flush
     * @var string $file Ruta al fichero
     * @return KlearMatrix_Model_Fso
     *
     * TODO: Comprobar que el $model implementa todo lo necesario para ser un módelo válido para ¿KlearMatrix?
     */
    public function put($file)
    {
        if (empty($file) or !file_exists($file)) {

            throw new \KlearMatrix_Exception_File('File not found');
        }

        $this->setBaseName(basename($file));
        $this->_setSrcFile($file);
        $this->_setSize(filesize($file));
        $this->_setMimeType($file);
        $this->_updateModelSpecs();
        $this->_mustFlush = true;
        return $this;
    }

    /**
     * @var string
     */
    public function setBaseName($name)
    {
        $this->_baseName = $name;
        return $this;
    }

    protected function _setSrcFile($filepath)
    {
        $this->_srcFile = $filepath;
        return $this;
    }

    /**
     * @var int
     */
    protected function _setSize($size)
    {
        $this->_size = $size;
        return $this;
    }

    protected function _setMimeType($file)
    {
        if (!is_null($file)) {

            $finfo = new finfo(FILEINFO_MIME);
            if ( $finfo ) {

                $this->_mimeType = $finfo->file($file);
            }
        }
    }

    protected function _updateModelSpecs()
    {
        $sizeSetter = 'set' . $this->_modelSpecs['sizeName'];
        $mimeSetter = 'set' . $this->_modelSpecs['mimeName'];
        $nameSetter = 'set' . $this->_modelSpecs['baseNameName'];

        $this->_model->{$sizeSetter}($this->getSize());
        $this->_model->{$mimeSetter}($this->getMimeType());
        $this->_model->{$nameSetter}($this->getBaseName());
    }

    /**
     * @return Klear_Model_Fso
     */
    public function flush($pk)
    {
        if (!$this->mustFlush()) {

            throw new Exception('Nothing to flush');
        }

        if (!is_numeric($pk)) {

            throw new Exception('Invalid Primary Key');
        }

        $targetPath = $this->_basePath . DIRECTORY_SEPARATOR . $this->_pk2path($pk);
        $targetFile = $targetPath . $pk;

        if (!file_exists($targetPath)) {

            if (!mkdir($targetPath, 0755, true)) {

                throw new Exception('Could not create dir ' . $targetPath);
            }
        }

        rename($this->_srcFile, $targetFile);

        clearstatcache();
        if ($this->getSize() != filesize($targetFile)) {

            $targetFileSize = filesize($targetFile);
            unlink($targetFile);
            throw new Exception('Something went wrong' . $this->getSize() . ' - ' . $tagetFileSize);
        }

        $this->_mustFlush = false;
        return $this;
    }

    /**
     * True if a new physic file has been set but is not still saved.
     * @return boolean
     */
    public function mustFlush()
    {
        return $this->_mustFlush;
    }


    /**
     * Converts id to path:
     *  1 => 0/1
     *  10 => 1/10
     *  15 => 1/15
     *  214 => 2/1/214
     * @return string
     */
    protected function _pk2path($pk)
    {
        $path = "";

        $aId = str_split((string)$pk);
        array_pop($aId);
        if (!sizeof($aId)) {
            $aId = array('0');
        }

        return implode(DIRECTORY_SEPARATOR, $aId) . DIRECTORY_SEPARATOR;
    }

    /**
     * Prepara el módelo para permitir la descarga del fichero llamando a getBinary()
     * @return KlearMatrix_Model_Fso
     */
    public function fetch()
    {
        $pk = $this->_model->getPrimaryKey();

        if (!is_numeric($pk) ) {

            throw new Exception("Empty object. No PK found");
        }

        $file = $this->_basePath . DIRECTORY_SEPARATOR . $this->_pk2path($pk) . $pk;

        if (!file_exists($file)) {

            throw new Exception("File $file not found");
        }

        $this->_setSize(filesize($file));
        $this->_setSrcFile($file);
        $this->_setMimeType($file);

        return $this;
    }

    /**
     * @var string
     * @var int
     */
    public function remove()
    {
        $pk = $model->getPrimaryKey();

        if (!is_numeric($pk)) {

            throw new Exception('Empty object. No PK found');

        }

        $file = $this->_basePath . DIRECTORY_SEPARATOR . $this->_pk2path($pk) . $pk;

        if (file_exists($file)) {

            unlink($file);

        } else {

            //TODO: loggear que el fichero que se intenta borrar no existe...
        }

        $this->_size = null;
        $this->_mimeType = null;
        $this->_binary = null;

        $this->_updateModelSpecs();

        return $this;
    }

    public function getBinary()
    {
        return file_get_contents($this->_srcFile);
    }

}
