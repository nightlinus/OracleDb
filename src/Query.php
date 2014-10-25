<?php
/**
 * Date: 24.10.14
 * Time: 23:15
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
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
     * @type string
     */
    public $text;

    /**
     * @type array
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
