<?php
/**
 * Date: 24.10.14
 * Time: 23:15
 *
 * @category Database
 * @package  nightlinus\OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/nightlinus/OracleDb
 */

namespace nightlinus\OracleDb;

/**
 * Class Query
 *
 * @package nightlinus\OracleDb
 */
class Query
{

    /**
     * @var string
     */
    public $text;

    /**
     * @var array
     */
    public $parameters;

    /**
     * @param       $text
     * @param array $parameters
     */
    public function __construct($text, array $parameters)
    {
        $this->text = $text;
        $this->parameters = $parameters;
    }
}
