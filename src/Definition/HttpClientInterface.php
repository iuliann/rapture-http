<?php

namespace Rapture\Http\Definition;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HttpClientInterface
 *
 * @package Rapture\Http
 * @author  Iulian N. <rapture@iuliann.ro>
 * @license LICENSE MIT
 */
interface HttpClientInterface
{
    /**
     * sendRequest
     *
     * @param RequestInterface $request Request object
     *
     * @return ResponseInterface
     */
    public function sendRequest(RequestInterface $request):ResponseInterface;
}
