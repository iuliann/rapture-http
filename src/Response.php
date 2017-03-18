<?php

namespace Rapture\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * PSR7 compliant **MUTABLE** Response object
 *
 * @package Rapture\Http
 * @author  Iulian N. <rapture@iuliann.ro>
 * @license LICENSE MIT
 */
class Response implements ResponseInterface
{
    // 1xx Informational
    const STATUS_CONTINUE = 100;
    const STATUS_SWITCHING_PROTOCOLS = 101;
    const STATUS_PROCESSING = 102;

    // 2xx Success
    const STATUS_OK = 200;
    const STATUS_CREATED = 201;
    const STATUS_ACCEPTED = 202;
    const STATUS_NON_AUTHORITATIVE_INFORMATION = 203;
    const STATUS_NO_CONTENT = 204;
    const STATUS_RESET_CONTENT = 205;
    const STATUS_PARTIAL_CONTENT = 206;
    const STATUS_MULTI_STATUS = 207;
    const STATUS_ALREADY_REPORTED = 208;
    const STATUS_IM_USED = 226;

    // 3xx Redirection
    const STATUS_MULTIPLE_CHOICES = 300;
    const STATUS_MOVED_PERMANENTLY = 301;
    const STATUS_FOUND = 302;
    const STATUS_SEE_OTHER = 303;
    const STATUS_NOT_MODIFIED = 304;
    const STATUS_USE_PROXY = 305;
    const STATUS_RESERVED = 306;
    const STATUS_TEMPORARY_REDIRECT = 307;
    const STATUS_PERMANENTLY_REDIRECT = 308;

    // 4xx Client Error
    const STATUS_BAD_REQUEST = 400;
    const STATUS_UNAUTHORIZED = 401;
    const STATUS_PAYMENT_REQUIRED = 402;
    const STATUS_FORBIDDEN = 403;
    const STATUS_NOT_FOUND = 404;
    const STATUS_METHOD_NOT_ALLOWED = 405;
    const STATUS_NOT_ACCEPTABLE = 406;
    const STATUS_PROXY_AUTHENTICATION_REQUIRED = 407;
    const STATUS_REQUEST_TIMEOUT = 408;
    const STATUS_CONFLICT = 409;
    const STATUS_GONE = 410;
    const STATUS_LENGTH_REQUIRED = 411;
    const STATUS_PRECONDITION_FAILED = 412;
    const STATUS_REQUEST_ENTITY_TOO_LARGE = 413;
    const STATUS_REQUEST_URI_TOO_LONG = 414;
    const STATUS_UNSUPPORTED_MEDIA_TYPE = 415;
    const STATUS_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const STATUS_EXPECTATION_FAILED = 417;
    const STATUS_I_AM_A_TEAPOT = 418;
    const STATUS_MISDIRECTED_REQUEST = 421;
    const STATUS_UNPROCESSABLE_ENTITY = 422;
    const STATUS_LOCKED = 423;
    const STATUS_FAILED_DEPENDENCY = 424;
    const STATUS_UPGRADE_REQUIRED = 426;
    const STATUS_PRECONDITION_REQUIRED = 428;
    const STATUS_TOO_MANY_REQUESTS = 429;
    const STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    const UNAVAILABLE_FOR_LEGAL_REASONS = 451;

    // 5xx Server Error
    const STATUS_INTERNAL_SERVER_ERROR = 500;
    const STATUS_NOT_IMPLEMENTED = 501;
    const STATUS_BAD_GATEWAY = 502;
    const STATUS_SERVICE_UNAVAILABLE = 503;
    const STATUS_GATEWAY_TIMEOUT = 504;
    const STATUS_VERSION_NOT_SUPPORTED = 505;
    const STATUS_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;
    const STATUS_INSUFFICIENT_STORAGE = 507;
    const STATUS_LOOP_DETECTED = 508;
    const STATUS_NOT_EXTENDED = 510;
    const STATUS_NETWORK_AUTHENTICATION_REQUIRED = 511;

    /** @var string */
    protected $version = '1.1';

    /** @var int */
    protected $status = self::STATUS_OK;

    /** @var string */
    protected $reason = 'OK';

    /** @var array */
    protected $headers = [];

    /** @var string */
    protected $body;

    /** @var array */
    protected $cookies = [];

    /**
     * Response constructor.
     *
     * @param string $body    Response body
     * @param int    $status  Status code
     * @param array  $headers Response headers array
     * @param string $version HTTP version
     */
    public function __construct($body = '', $status = self::STATUS_OK, array $headers = [], $version = '1.1')
    {
        if (!($body instanceof StreamInterface)) {
            $string = $body;
            $body = new Stream(fopen('php://memory', 'r+'));
            $body->write($string);
        }

        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers;
        $this->version = $version;
    }

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        return $this->version;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     *
     * @return self
     */
    public function withProtocolVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     * // Represent the headers as a string
     * foreach ($message->getHeaders() as $name => $values) {
     * echo $name . ": " . implode(", ", $values);
     * }
     *
     * // Emit headers iteratively:
     * foreach ($message->getHeaders() as $name => $values) {
     * foreach ($values as $value) {
     * header(sprintf('%s: %s', $name, $value), false);
     * }
     * }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return array Returns an associative array of the message's headers. Each
     * key MUST be a header name, and each value MUST be an array of strings
     * for that header.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     *
     * @return bool Returns true if any header names match the given header
     * name using a case-insensitive string comparison. Returns false if
     * no matching header name is found in the message.
     */
    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param string $name Case-insensitive header field name.
     *
     * @return string[] An array of string values as provided for the given
     * header. If the header does not appear in the message, this method MUST
     * return an empty array.
     */
    public function getHeader($name)
    {
        return $this->hasHeader($name)
            ? $this->headers[$name]
            : [];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     *
     * @return string A string of values as provided for the given header
     * concatenated together using a comma. If the header does not appear in
     * the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name)
    {
        return implode(',', $this->getHeader($name));
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string          $name  Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     *
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value)
    {
        $this->headers[$name] = (array)$value;

        return $this;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new header and/or value.
     *
     * @param string          $name  Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     *
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value)
    {
        if ($this->hasHeader($name)) {
            $this->headers[$name] = array_merge($this->headers[$name], (array)$value);
        } else {
            $this->headers[$name] = (array)$value;
        }

        return $this;
    }

    /**
     * Return an instance without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     *
     * @return self
     */
    public function withoutHeader($name)
    {
        unset($this->headers[$name]);

        return $this;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamInterface $body Body.
     *
     * @return self
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @param int    $code         The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *                             provided status code; if none is provided, implementations MAY
     *                             use the defaults as suggested in the HTTP specification.
     *
     * @return self
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $this->status = $code;

        if ($reasonPhrase == '') {
            $constant = array_search($code, (new \ReflectionClass($this))->getConstants());

            if ($constant === false) {
                throw new \InvalidArgumentException('Invalid status code.');
            }

            $reasonPhrase = ucwords(strtolower(str_replace('_', ' ', substr($constant, 7))));
        }

        $this->reason = $reasonPhrase;

        return $this;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase()
    {
        return $this->reason;
    }

    /*
    * Extra
    */

    /**
     * withCookie
     *
     * @param Cookie $cookie Cookie object
     *
     * @return $this
     */
    public function withCookie(Cookie $cookie)
    {
        $this->cookies[$cookie->getName()] = $cookie;

        return $this;
    }

    /**
     * Remove cookie
     *
     * @param string $name Cookie name
     *
     * @return $this
     */
    public function withoutCookie($name)
    {
        unset($this->cookies[$name]);

        return $this;
    }

    /**
     * send
     *
     * @return void
     */
    public function send()
    {
        if (!headers_sent()) {
            header(
                sprintf('HTTP/%s %s %s', $this->version, $this->status, $this->reason),
                true,
                $this->status
            );

            foreach ($this->headers as $name => $value) {
                header($name . ': ' . implode(', ', (array)$value), false, $this->status);
            }

            /** @var Cookie $cookie */
            foreach ((array)$this->cookies as $cookie) {
                setcookie(
                    $cookie->getName(),
                    $cookie->getValue(),
                    $cookie->getExpire(),
                    $cookie->getPath(),
                    $cookie->getDomain(),
                    $cookie->isSecure(),
                    $cookie->isHttpOnly()
                );
            }
        }

        echo (string)$this->body;
    }
}
