<?php
/**
 * Date: 07.10.14
 * Time: 12:55
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
 */

namespace nightlinus\OracleDb\Driver;


class Exception extends \Exception
{
    /**
     * @param string|array $message
     * @param int          $code
     * @param \Exception   $previous
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
        parent::__construct($message, $code, $previous);
    }
}
