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
            $code = isset($message[ 'code' ]) ? $message[ 'code' ] : null;
            if (!$code) {
                $code = isset($message[ 'type' ]) ? $message[ 'type' ] : null;
            }
            $text = $message[ 'message' ];
            $offset = isset($message[ 'offset' ]) ? $message[ 'offset' ] : null;
            $sql = isset($message[ 'sqltext' ]) ? $message[ 'sqltext' ]: null;
            $message = $text;
            if ($offset && $sql) {
                $message .= "\nOffset: $offset" .
                    "\n$sql";
            }
        }
        parent::__construct($message, $code, $previous);
    }
}
