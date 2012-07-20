<?php
/***
 * File system object
 * FIXME: Hacer funcionar correctamente fetch y remove.
*/
class KlearMatrix_Model_Fso
{
    const CLASS_ATTR_SEPARATOR = '.';

    private $_storagePath = '';

    protected $_pk;

    protected $_srcFile;

    protected $_size = null;

    protected $_basePath = '';
    protected $_path;
    protected $_baseName = '';
    protected $_mimeType;

    protected $_mustFlush = false;

    public function __construct()
    {
        $bootstrap = \Zend_Controller_Front::getInstance()->getParam('bootstrap');

        if (is_null($bootstrap)) {

            $conf = new \Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);

        } else {

            $conf = (Object) $bootstrap->getOptions();
        }

        $this->_setLocalStorage($conf);

    }

    protected function _setLocalStorage($conf)
    {
        if (isset($conf->localStoragePath)) {

            $this->_storagePath = $conf->localStoragePath;
            if (substr($this->_storagePath, -1) != DIRECTORY_SEPARATOR) {
                $this->_storagePath .= DIRECTORY_SEPARATOR;
            }

        } else {

            $this->_storagePath = APPLICATION_PATH . '/../storage/';
        }
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
     * @var string $basePath
     * @var string $file
     * @var int $pk
     * @return KlearMatrix_Model_Fso
     *
     * TODO: Comprobar que el $model implementa todo lo necesario para ser un módelo válido para ¿KlearMatrix?
     */
    public function put($specs, $file, $model)
    {
        $modelClassName = $this->_getModelClassName($model);
        $basePath = $modelClassName . self::CLASS_ATTR_SEPARATOR . $specs['basePath'];

        if (empty($file) or !file_exists($file)) {

            throw new \KlearMatrix_Exception_File('File not found');
        }

        $this->_basePath = strtolower($basePath);

        $this->_setSrcFile($file);
        $this->_setSize(filesize($file));
        $this->setBaseName(basename($file));
        $this->_setMimeType($file);

        $this->_updateModelSpecs($model, $specs);
        $this->_mustFlush = true;
        return $this;
    }

    protected function _setSrcFile($filepath)
    {
        $this->_srcFile = $filepath;
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

    /**
     * @var string
     */
    public function setBaseName($name)
    {
        $this->_baseName = $name;
        return $this;
    }

    protected function _updateModelSpecs($model, $specs)
    {

        $sizeSetter = 'set' . $specs['sizeName'];
        $mimeSetter = 'set' . $specs['mimeName'];
        $nameSetter = 'set' . $specs['baseNameName'];

        $model->{$sizeSetter}($this->getSize());
        $model->{$mimeSetter}($this->getMimeType());
        $model->{$nameSetter}($this->getBaseName());

    }

    /**
     * @return Klear_Model_Fso
     */
    public function flush($pk)
    {
        //TODO: Mejorar esta comprobación, no parece lo más obvio (tenemos un mustFlush por ahí...)
        if (!$this->mustFlush()) {

            throw new Exception('Nothing to flush');
        }

        if (!is_numeric($pk)) {

            throw new Exception('Invalid Primary Key');
        }

        $this->_pk = $pk;
        $this->_path = $this->_pk2path($this->_pk);

        $targetPath = $this->_storagePath . $this->_basePath . DIRECTORY_SEPARATOR . $this->_path;
        $targetFile = $targetPath . $this->_pk;

        if (!file_exists($targetPath)) {

            if (!mkdir($targetPath, 0755, true)) {

                throw new Exception('Could not create dir ' . $targetPath);
            }
        }

        rename($this->_srcFile, $targetFile);

        if ($this->getSize() != filesize($targetFile)) {

            unlink($targetFile);
            throw new Exception('Something went wrong' . $this->getSize() . ' - ' . filesize($targetFile));
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
     * //FIXME: Implementar esto bien, ahora está copiado de EKT_MODEL_FSO, not working
     * @var string $basePath
     * @var int $pk
     * @return EKT_Model_FSO
     */
    public function fetch($specs, $model)
    {
        $modelClassName = $this->_getModelClassName($model);
        $basePath = $modelClassName . self::CLASS_ATTR_SEPARATOR . $specs['basePath'];

        $pk = $model->getPrimaryKey();

        if ( ! is_numeric($pk) ) {

            throw new Exception("Empty object. No PK found");
        }

        if (! is_null($pk)) {

            $this->_pk = $pk;
        }

        if ( $this->_basePath != $basePath
                or $this->_path != $this->_pk2path($this->_pk)
        ) {

            $this->_basePath = $basePath;
            $this->_path = $this->_pk2path($this->_pk);

            if (empty($this->_storagePath)) {

                $soapClient = $this->_getSoapClient();

                $response = $soapClient->FSO_fetch($specs, addcslashes(serialize($model), "\0\\"));

                if (! is_null($response)) {

                    $response = unserialize(stripcslashes($response));
                }

                $this->_size = $response->getSize();
                $this->_mimeType = $response->getMimeType();
                $this->_binary = $response->getBinary();
                $this->_b64encoded = false;

            } else {

                $file = $this->_storagePath . strtolower($basePath) . DIRECTORY_SEPARATOR . $this->_path . $this->_pk;

                if (! file_exists($file)) {

                    throw new Exception("File $file not found");
                }

                $this->_setSize(filesize($file));
                $this->_setBinary($file);
                $this->_setMimeType($file);
            }
        }

        return $this;
    }

    protected function _getModelClassName($model)
    {
        return str_replace('\\', '_', get_class($model));
    }

    /**
     * @var string
     * @var int
     */
    public function remove($specs, $model = null)
    {
        $basePath = $specs['basePath'];

        if (!$model instanceof EKT_Model_Raw_ModelAbstract) {

            throw new Exception('Invalid EKT_Model');
        }


        $pk = $model->getPrimaryKey();

        if (! is_numeric($this->_pk) and ! is_numeric($pk)) {

            throw new Exception('Invalid PK given');

        } else {

            $this->_pk = $pk;
        }

        if ( $this->_basePath != $basePath
                or $this->_path != $this->_pk2path($this->_pk)
        ) {

            $this->_basePath = $basePath;
            $this->_path = $this->_pk2path($this->_pk);
        }

        $file = $this->_storagePath . $basePath . DIRECTORY_SEPARATOR . $this->_path . $this->_pk;

        if (file_exists($file)) {

            unlink($file);

        } else {

            //TODO: loggear que el fichero que se intenta borrar no existe...
        }

        $this->_size = null;
        $this->_mimeType = null;
        $this->_binary = null;

        $this->_updateModelSpecs($model, $specs);

        return $this;
    }

}
