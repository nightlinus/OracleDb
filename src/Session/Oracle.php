<?php
/**
 * Date: 24.10.14
 * Time: 0:06
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
 */

namespace nightlinus\OracleDb\Session;

use nightlinus\OracleDb\Config;
use nightlinus\OracleDb\Database;

/**
 * Class Oracle
 *
 * @package nightlinus\OracleDb\Session
 */
class Oracle
{

    /**
     * @type Database
     */
    protected $db;

    /**
     * @param $db
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * @return $this
     */
    public function setupBeforeConnect()
    {
        $connectionClass = $this->db->config(Config::CONNECTION_CLASS);
        if ($connectionClass) {
            ini_set('oci8.connection_class', $connectionClass);
        }
        $edition = $this->db->config(Config::CONNECTION_EDITION);
        if ($edition) {
            $this->db->getDriver()->setEdition($edition);
        }

        return $this;
    }


    /**
     *
     */
    public function apply()
    {
        $handle = $this->db->getConnection();
        $driver = $this->db->getDriver();
        $driver->setClientIdentifier($handle, $this->db->config(Config::CLIENT_IDENTIFIER));
        $driver->setClientInfo($handle, $this->db->config(Config::CLIENT_INFO));
        $driver->setClientModuleName($handle, $this->db->config(Config::CLIENT_MODULE_NAME));
        $this->setVariables($this->extractFromConfig());

        return $this;
    }

    /**
     * @return array
     */
    public function extractFromConfig()
    {
        $setUp = [ ];
        if ($this->db->config(Config::SESSION_DATE_FORMAT)) {
            $setUp[ 'NLS_DATE_FORMAT' ] = $this->db->config(Config::SESSION_DATE_FORMAT);
        }
        if ($this->db->config(Config::SESSION_DATE_LANGUAGE)) {
            $setUp[ 'NLS_DATE_LANGUAGE' ] = $this->db->config(Config::SESSION_DATE_LANGUAGE);
        }
        if ($this->db->config(Config::SESSION_CURRENT_SCHEMA)) {
            $setUp[ 'CURRENT_SCHEMA' ] = $this->db->config(Config::SESSION_CURRENT_SCHEMA);
        }

        return $setUp;
    }

    /**
     * @param $variables
     *
     * @return $this
     */
    public function setVariables($variables)
    {
        if (count($variables) === 0) {
            return $this;
        }
        $sql = "ALTER SESSION SET ";
        foreach ($variables as $key => $value) {
            $sql .= "$key = '$value' ";
        }
        $this->db->query($sql);

        return $this;
    }
}
