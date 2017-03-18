<?php

namespace Rapture\Http\Exception;

use Rapture\Http\Definition\HttpExceptionInterface;
use Rapture\Http\Definition\HttpExceptionDataTrait;

/**
 * HttpBadRequestException
 *
 * @package Rapture\Http
 * @author  Iulian N. <rapture@iuliann.ro>
 * @license LICENSE MIT
 */
class HttpBadRequestException extends \Exception implements HttpExceptionInterface
{
    use HttpExceptionDataTrait;

    /**
     * @param string     $message  Exception message
     * @param int        $code     Exception code
     * @param \Exception $previous Previous exception
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message ?: 'Bad Request', 400, $previous);
    }
}
