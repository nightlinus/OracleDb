<?php
/**
 * Date: 24.10.14
 * Time: 0:06
 *
 * @category Database
 * @package  nightlinus\OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/nightlinus/OracleDb
 */

namespace nightlinus\OracleDb\Session;

use nightlinus\OracleDb\Config;
use nightlinus\OracleDb\Driver\AbstractDriver;

/**
 * Class Oracle
 */
class Oracle
{
    /**
     * @var AbstractDriver
     */
    private $driver;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param AbstractDriver $driver
     * @param Config         $config
     */
    public function __construct(AbstractDriver $driver, Config $config)
    {
        $this->config = $config;
        $this->driver = $driver;
    }

    /**
     * @param $handle
     *
     * @return string
     * @throws \nightlinus\OracleDb\Exception
     */
    public function apply($handle)
    {
        $this->driver->setClientIdentifier($handle, $this->config->get(Config::CLIENT_IDENTIFIER));
        $this->driver->setClientInfo($handle, $this->config->get(Config::CLIENT_INFO));
        $this->driver->setClientModuleName($handle, $this->config->get(Config::CLIENT_MODULE_NAME));

        return $this->generateSql($this->extractVariablesFromConfig());
    }

    /**
     * @return $this
     */
    public function setupBeforeConnect()
    {
        $connectionClass = $this->config->get(Config::CONNECTION_CLASS);
        if ($connectionClass) {
            ini_set('oci8.connection_class', $connectionClass);
        }
        $edition = $this->config->get(Config::CONNECTION_EDITION);
        if ($edition) {
            $this->driver->setEdition($edition);
        }

        return $this;
    }

    /**
     * @return array
     */
    private function extractVariablesFromConfig()
    {
        $setUp = [];
        $dateFormat = $this->config->get(Config::SESSION_DATE_FORMAT);
        if ($dateFormat) {
            $setUp[ 'NLS_DATE_FORMAT' ] = $dateFormat;
        }
        $dateLanguage = $this->config->get(Config::SESSION_DATE_LANGUAGE);
        if ($dateLanguage) {
            $setUp[ 'NLS_DATE_LANGUAGE' ] = $dateLanguage;
        }
        $schema = $this->config->get(Config::SESSION_CURRENT_SCHEMA);
        if ($schema) {
            $setUp[ 'CURRENT_SCHEMA' ] = $schema;
        }

        return $setUp;
    }

    /**
     * @param $variables
     *
     * @return $this
     */
    private function generateSql($variables)
    {
        if (count($variables) === 0) {
            return $this;
        }
        $sql = "ALTER SESSION SET ";
        foreach ($variables as $key => $value) {
            $sql .= "$key = '$value' ";
        }

        return $sql;
    }
}
