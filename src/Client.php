<?php

namespace Rapture\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rapture\Http\Definition\HttpClientInterface;

/**
 * Class Client
 *
 * @package Rapture\Http
 * @author  Iulian N. <rapture@iuliann.ro>
 * @license LICENSE MIT
 * @credits https://github.com/php-http/curl-client
 */
class Client implements HttpClientInterface
{
    /** @var resource */
    protected $handler;

    protected $options = [];

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function setOptions(array $options)
    {
        $this->options = $options + $this->options;

        return $this;
    }

    public function sendRequest(RequestInterface $request):ResponseInterface
    {
        if ($this->handler) {
            curl_reset($this->handler);
        }

        if (!$this->handler) {
            $this->handler = curl_init();
        }

        $response = new Response();
        curl_setopt_array($this->handler, $this->getCurlOptions($request, $response) + $this->options + $this->getDefaultOptions());
//        curl_exec($this->handler); // todo find a fix
        $response->getBody()->write(curl_exec($this->handler));

        if (curl_errno($this->handler) > 0) {
            throw new \RuntimeException(curl_error($this->handler));
        }

        return $response;
    }

    protected function getDefaultOptions()
    {
        return [
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_NONE,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'RaptureHttpClient 1.0 cURL/' . curl_version()['version'],
            CURLOPT_HEADER         => false,
            CURLOPT_RETURNTRANSFER => true,
        ];
    }

    /**
     * getRequestOptions
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return array
     */
    protected function getCurlOptions(RequestInterface $request, ResponseInterface $response)
    {
        /*
         * Clarification
         *
         * @credits http://php.net/manual/en/function.curl-setopt.php#66832
         * - CURLOPT_HEADERFUNCTION = handling header lines received *in the response*,
         * - CURLOPT_WRITEFUNCTION  = handling data received *from the response*,
         * - CURLOPT_READFUNCTION   = handling data passed along *in the request*.
         */

        $method = $request->getMethod();
        $options[CURLOPT_URL] = (string)$request->getUri();
        $options[CURLOPT_HTTP_VERSION] = constant('CURL_HTTP_VERSION_' . str_replace('.', '_', $request->getProtocolVersion()));

        if ($method == Request::METHOD_HEAD) {
            $options[CURLOPT_NOBODY] = true;
        } elseif ($method !== Request::METHOD_GET) {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
            /** @var Request $request */
            $body = $request->getParsedBody();
            if ($body) {
                if ($request->getHeaderLine('content-type') == 'application/json') {
                    $options[CURLOPT_POSTFIELDS] = is_array($body) ? json_encode($body) : $body;
                }
                else {
                    $options[CURLOPT_POSTFIELDS] = is_array($body) ? http_build_query($body) : $body;
                }
            }
        }

        $options[CURLOPT_HTTPHEADER] = $this->getCurlHeaders($request, $options);

        if ($request->getUri()->getUserInfo()) {
            $options[CURLOPT_USERPWD] = $request->getUri()->getUserInfo();
        }

        $options[CURLOPT_HEADERFUNCTION] = function ($ch, $data) use ($response) {
            $header = trim($data);
            if ($header) {
                if (strpos(strtolower($header), 'http/') === 0) {
                    $response->withStatus((int)substr($header, strpos($header, ' ') + 1));
                } else {
                    $response->withHeader(
                        substr($header, 0, strpos($header, ':')),
                        substr($header, strpos($header, ':') + 1)
                    );
                }
            }

            return strlen($data);
        };

        // todo find a fix
        $options[CURLOPT_WRITEFUNCTION] = function ($ch, $data) use (&$response) {
            return (int)$response->getBody()->write($data);
        };

        return $options;
    }

    /**
     * getCurlHeaders
     *
     * @param RequestInterface $request Request object
     * @param array            $options cURL options
     *
     * @credits https://github.com/php-http/curl-client/
     *
     * @return \string[]
     */
    protected function getCurlHeaders(RequestInterface $request, array $options)
    {
        $curlHeaders = [];
        $headers = array_keys($request->getHeaders());

        foreach ($headers as $name) {
            $header = strtolower($name);

            if ('expect' === $header) {
                // curl-client does not support "Expect-Continue", so dropping "expect" headers
                // @credits https://github.com/php-http/curl-client
                continue;
            }

            if ('content-length' === $header) {
                $value = 0;
                if (array_key_exists(CURLOPT_POSTFIELDS, $options)) {
                    $value = strlen($options[CURLOPT_POSTFIELDS]);
                }
            } else {
                $value = $request->getHeaderLine($name);
            }

            $curlHeaders[] = $name . ': ' . $value;
        }

        /*
         * curl-client does not support "Expect-Continue", but cURL adds "Expect" header by default.
         * We can not suppress it, but we can set it to empty.
         * @credits https://github.com/php-http/curl-client/
         */
        $curlHeaders[] = 'Expect:';

        return $curlHeaders;
    }
}
