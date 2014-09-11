<?php
/**
 * Package exception Class
 *
 * PHP version 5
 *
 * @category Database
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version  GIT: 1
 * @link     https://github.com/nightlinus/OracleDb
 */

namespace nightlinus\OracleDb;

/**
 * Class Exception
 * @package Oracle
 */
class Exception extends \Exception
{
    /**
     * @param string $message
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        if (func_num_args() === 1 && is_array($message)) {
            $code = $message[ 'code' ];
            $text = $message[ 'message' ];
            $offset = $message[ 'offset' ];
            $sql = $message[ 'sqltext' ];
            $message = $text;
            if ($offset && $sql) {
                $message .= "\nOffset: $offset" .
                            "\n$sql";
            }
        }
        \Exception::__construct($message, $code, $previous);
    }
}
