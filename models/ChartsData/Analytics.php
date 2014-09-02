<?php
/**
 * Conecta con Google Analytics y para importar los datos definidos en el .yaml
 * los cuales se visualizaran el los GoogleCharts de Kleak.
 *
 * Todo los posibles fallos por falta de parametros necesarios, estan
 * controlados con Exceptions de klear.
 *
 * Al no definir una la fecha de inicio y fin por defecto
 * se interpreta como una consulta del año en curso.
 *
 * Documentación para el uso e implementación en:
 * http://klear-tutorial.irontec.com/yaml/charts.html
 * https://developers.google.com/analytics/devguides/reporting/core/dimsmets
 *
 * @author ddniel16
 */

class KlearMatrix_Model_ChartsData_Analytics
{

    protected $_profileId;
    protected $_username;
    protected $_password;

    protected $_metric;
    protected $_dimension;

    protected $_analyticsClient;

    protected $_maxResults;
    protected $_startDate;
    protected $_endDate;

    public function __construct($analytics)
    {

        $this->setConfig($analytics);
        $this->_clientLogin();

    }

    /**
     * Comprueba que los datos principales para la conección
     * con Google Analytics existan y hace su debido set.
     */
    public function setConfig($params)
    {

        if (isset($params->config->profileId)) {
            $this->_profileId = $params->config->profileId;
        } else {
            $this->_error(
                '"profileId" de analytics no definido'
            );
        }

        if (isset($params->config->username)) {
            $this->_username = $params->config->username;
        } else {
            $this->_error(
                '"username" de analytics no definido'
            );
        }

        if (isset($params->config->password)) {
            $this->_password = $params->config->password;
        } else {
            $this->_error(
                '"password" de analytics no definido'
            );
        }

        if (isset($params->config->maxResults)) {
            $this->_maxResults = $params->config->maxResults;
        } else {
            $this->_maxResults = 1;
        }

        if (isset($params->config->startDate)) {
            $this->_startDate = $params->config->startDate;
        } else {
            $this->_startDate = 'YYYY-01-01';
        }

        if (isset($params->config->endDate)) {
            $this->_endDate = $params->config->endDate;
        } else {
            $this->_endDate = 'YYYY-MM-dd';
        }

        $translator = Zend_Registry::get(
            Klear_Plugin_Translator::DEFAULT_REGISTRY_KEY
        );

        if (isset($params->metric)) {

            $metrics = array();
            foreach ($params->metric as $key => $val) {

                $literal = Klear_Model_Gettext::gettextCheck(
                    $translator->translate($val)
                );

                $metrics[]= array(
                    'key' => $key,
                    'title' => $literal
                );

            }

            $this->_metric = $metrics;

        } else {
            $this->_error('"metric" no definidas.');
        }

        if (isset($params->dimension)) {

            $dimensions = array();
            foreach ($params->dimension as $key => $val) {

                $literal = Klear_Model_Gettext::gettextCheck(
                    $translator->translate($val)
                );

                $dimensions[]= array(
                    'key' => $key,
                    'title' => $literal
                );

            }

            $this->_dimension = $dimensions;

        } else {
            $this->_error('"dimension" no definidas.');
        }

    }

    protected function _clientLogin()
    {

        $service = Zend_Gdata_Analytics::AUTH_SERVICE_NAME;
        $client = Zend_Gdata_ClientLogin::getHttpClient(
            $this->_username,
            $this->_password,
            $service
        );

        $this->_analyticsClient = new Zend_Gdata_Analytics($client);

    }

    public function getAnalyticsData()
    {

        $date = new Zend_Date();

        $analyticsQuery = $this->_analyticsClient
            ->newDataQuery()
            ->setProfileId($this->_profileId);

        $metricTitles = array();
        foreach ($this->_metric as $metricItem) {
            $analyticsQuery->addMetric($metricItem['key']);
            $analyticsQuery->addSort($metricItem['key'], true);
            $metricTitles[] = $metricItem['title'];
        }

        $dimensionTitles = array();
        foreach ($this->_dimension as $dimensionItem) {
            $analyticsQuery->addDimension($dimensionItem['key']);
            $dimensionTitles[] = $dimensionItem['title'];
        }

        $analyticsQuery->setStartDate(
            $date->toString(
                $this->_startDate
            )
        );

        $analyticsQuery->setEndDate(
            $date->toString(
                $this->_endDate
            )
        );

        $analyticsQuery->setMaxResults(
            $this->_maxResults
        );

        $result = $this->_analyticsClient->getDataFeed($analyticsQuery);

        $table = array();
        $firstRow = array();

        foreach ($dimensionTitles as $dimensionTitle) {
            $firstRow[] = $dimensionTitle;

        }

        foreach ($metricTitles as $metricTitle) {
            $firstRow[] = $metricTitle;
        }

        $table[] = $firstRow;

        foreach ($result as $row) {

            $rowTable = array();

            foreach ($this->_dimension as $dimensionItem) {
                $rowTable[] = $row->getDimension(
                    $dimensionItem['key']
                )->value;
            }

            foreach ($this->_metric as $metricItem) {
                $rowTable[] = floatval(
                    $row->getMetric($metricItem['key'])->value
                );
            }

            $table[] = $rowTable;

        }

        return $table;

    }

    /**
     * @param String $message
     * @throws Klear_Exception_Default
     */
    protected function _error($message)
    {
        throw new Klear_Exception_Default($message);
    }

}