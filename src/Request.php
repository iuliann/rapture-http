<?php

namespace Rapture\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * PSR7 compliant **MUTABLE** Request object
 *
 * @package Rapture\Http
 * @author  Iulian N. <rapture@iuliann.ro>
 * @license LICENSE MIT
 */
class Request implements ServerRequestInterface
{
    const METHOD_HEAD = 'HEAD';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PURGE = 'PURGE';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_TRACE = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';

    /** @var string */
    protected $version = '1.1';

    /** @var string */
    protected $method = self::METHOD_GET;

    /** @var array */
    protected $query = [];

    /** @var array */
    protected $post = [];

    /** @var array */
    protected $put = [];

    /** @var array */
    protected $files = [];

    /** @var array */
    protected $server = [];

    /** @var mixed */
    protected $headers = [];

    /** @var array */
    protected $cookies = [];

    /** @var Uri */
    protected $uri;

    /** @var array $attributes */
    protected $attributes = [];

    /** @var mixed */
    protected $body;

    /**
     * @param Uri    $uri        URI object
     * @param string $method     HTTP method
     * @param array  $headers    Headers array
     * @param array  $attributes Attributes array
     * @param array  $globals    PHP globals
     * @param string $version    HTTP version
     */
    public function __construct(Uri $uri = null, $method = self::METHOD_GET, array $headers = [], array $attributes = [], array $globals = [], $version = '1.1')
    {
        $this->query = isset($globals['query']) ? (array)$globals['query'] : [];
        $this->post = isset($globals['post']) ? (array)$globals['post'] : [];
        $this->files = isset($globals['files']) ? self::parseFiles($globals['files']) : [];
        $this->server = isset($globals['server']) ? (array)$globals['server'] : [];
        $this->cookies = isset($globals['cookies']) ? self::parseCookies($globals['cookies']) : [];

        $this->uri = $uri ?: new Uri('/');
        $this->method = $method;
        $this->headers = $headers + self::parseHeaders($this->server);
        $this->version = $version;
        $this->attributes = $attributes;
    }

    /**
     * @param array $attributes Attributes array
     *
     * @return Request
     */
    public static function createFromGlobals(array $attributes = [])
    {
        $queryPos = strpos($_SERVER['REQUEST_URI'], '?');

        $url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://')
            . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT']
            . substr($_SERVER['REQUEST_URI'], 0, $queryPos)
            . substr($_SERVER['REQUEST_URI'], $queryPos);

        return new Request(
            new Uri($url),
            $_SERVER['REQUEST_METHOD'],
            [],
            $attributes,
            [
                'query'   => $_GET,
                'post'    => $_POST,
                'files'   => $_FILES,
                'server'  => $_SERVER,
                'cookies' => $_COOKIE,
            ],
            substr($_SERVER['SERVER_PROTOCOL'], 5)
        );
    }

    /**
     * @param array $input Input array
     *
     * @return array
     */
    public static function parseHeaders(array $input)
    {
        $headers = [];

        foreach ($input as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $headers[strtolower(str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5))))))] = explode(',', $value);
            }
        }

        return $headers;
    }

    /**
     * parseFiles
     *
     * @param array $files Files array
     *
     * @return UploadedFile[]
     */
    public static function parseFiles(array $files)
    {
        foreach ($files as $file => $data) {
            if (isset($data['tmp_name'])) {
                $files[$file] = new UploadedFile($data);
            } else {
                $files[$file] = self::parseFiles($files[$file]);
            }
        }

        return $files;
    }

    /**
     * parseCookies
     *
     * @param array $cookies Cookies array
     *
     * @return Cookie[]
     */
    public static function parseCookies(array $cookies)
    {
        foreach ($cookies as $name => $value) {
            $cookies[$name] = new Cookie($name, $value);
        }

        return $cookies;
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
        return isset($this->headers[strtolower($name)])
            ? $this->headers[strtolower($name)]
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
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget()
    {
        // TODO: Implement getRequestTarget() method.
        return '';
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-2.7 (for the various
     *     request-target forms allowed in request messages)
     *
     * @param mixed $requestTarget
     *
     * @return self
     */
    public function withRequestTarget($requestTarget)
    {
        // TODO: Implement withRequestTarget() method.
        return $this;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     *
     * @return self
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        if (!isset($this->getValidMethods()[$method])) {
            throw new \InvalidArgumentException('Invalid method: ' . $method);
        }

        $this->method = $method;

        return $this;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     *
     * @param UriInterface $uri          New request URI to use.
     * @param bool         $preserveHost Preserve the original state of the Host header.
     *
     * @return self
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        if ($preserveHost) {
            $uri->withHost($this->uri->getHost());
        }

        $this->uri = $uri;

        return $this;
    }

    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->server;
    }

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookies;
    }

    /**
     * Return an instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST NOT update the related Cookie header of the request
     * instance, nor related values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     *
     * @return self
     */
    public function withCookieParams(array $cookies)
    {
        $this->cookies = $cookies;

        return $this;
    }

    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URI or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the query string from `getUri()->getQuery()`
     * or from the `QUERY_STRING` server param.
     *
     * @return array
     */
    public function getQueryParams()
    {
        return $this->query;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URI stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *                     $_GET.
     *
     * @return self
     */
    public function withQueryParams(array $query)
    {
        $this->query = $query;
        $this->uri->withQueryParams($query);

        return $this;
    }

    /**
     * Retrieve normalized file upload data.
     *
     * This method returns upload metadata in a normalized tree, with each leaf
     * an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadedFiles().
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles()
    {
        return $this->files;
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
     *
     * @return self
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        foreach ($uploadedFiles as $name => $data) {
            $this->files[$name] = new UploadedFile($data);
        }

        return $this;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method MUST
     * return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     */
    public function getParsedBody()
    {
        if ($this->getMethod() == self::METHOD_POST) {
            return $this->getPostParams();
        }

        return [];
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * These MAY be injected during instantiation.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, use this method
     * ONLY to inject the contents of $_POST.
     *
     * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
     * deserializing the request body content. Deserialization/parsing returns
     * structured data, and, as such, this method ONLY accepts arrays or objects,
     * or a null value if nothing was available to parse.
     *
     * As an example, if content negotiation determines that the request data
     * is a JSON payload, this method could be used to create a request
     * instance with the deserialized parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param null|array|object $data The deserialized body data. This will
     *                                typically be in an array or object.
     *
     * @return self
     * @throws \InvalidArgumentException if an unsupported argument type is
     *     provided.
     */
    public function withParsedBody($data)
    {
        // TODO: Implement withParsedBody() method.
        return $this;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * de-serializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     *
     * @param string $name    The attribute name.
     * @param mixed  $default Default value to return if the attribute does not exist.
     *
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        return isset($this->attributes[$name])
            ? $this->attributes[$name]
            : $default;
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     *
     * @param string $name  The attribute name.
     * @param mixed  $value The value of the attribute.
     *
     * @return self
     */
    public function withAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     *
     * @param string $name The attribute name.
     *
     * @return self
     */
    public function withoutAttribute($name)
    {
        unset($this->attributes[$name]);

        return $this;
    }

    /*
     * HELPERS
     */

    protected function getValidMethods()
    {
        return [
            self::METHOD_HEAD    => self::METHOD_HEAD,
            self::METHOD_GET     => self::METHOD_GET,
            self::METHOD_POST    => self::METHOD_POST,
            self::METHOD_PUT     => self::METHOD_PUT,
            self::METHOD_PATCH   => self::METHOD_PATCH,
            self::METHOD_DELETE  => self::METHOD_DELETE,
            self::METHOD_PURGE   => self::METHOD_PURGE,
            self::METHOD_OPTIONS => self::METHOD_OPTIONS,
            self::METHOD_TRACE   => self::METHOD_TRACE,
            self::METHOD_CONNECT => self::METHOD_CONNECT,
        ];
    }

    /**
     * isHead
     *
     * @return bool
     */
    public function isHead():bool
    {
        return $this->method === self::METHOD_HEAD;
    }

    /**
     * isGet
     *
     * @return bool
     */
    public function isGet():bool
    {
        return $this->method === self::METHOD_GET;
    }

    /**
     * isPost
     *
     * @return bool
     */
    public function isPost():bool
    {
        return $this->method === self::METHOD_POST;
    }

    /**
     * isPut
     *
     * @return bool
     */
    public function isPut():bool
    {
        return $this->method === self::METHOD_PUT;
    }

    /**
     * isPatch
     *
     * @return bool
     */
    public function isPatch():bool
    {
        return $this->method === self::METHOD_PATCH;
    }

    /**
     * isDelete
     *
     * @return bool
     */
    public function isDelete():bool
    {
        return $this->method === self::METHOD_DELETE;
    }

    /**
     * isConnect
     *
     * @return bool
     */
    public function isConnect():bool
    {
        return $this->method === self::METHOD_CONNECT;
    }

    /**
     * isOptions
     *
     * @return bool
     */
    public function isOptions():bool
    {
        return $this->method === self::METHOD_OPTIONS;
    }

    /**
     * isPurge
     *
     * @return bool
     */
    public function isPurge():bool
    {
        return $this->method === self::METHOD_PURGE;
    }

    /**
     * isTrace
     *
     * @return bool
     */
    public function isTrace():bool
    {
        return $this->method === self::METHOD_TRACE;
    }

    /**
     * isAjax
     *
     * @return bool
     */
    public function isAjax():bool
    {
        return strtolower(implode(',', $this->getHeader('X-Requested-With'))) == 'xmlhttprequest';
    }

    /**
     * isSecure
     *
     * @return bool
     */
    public function isSecure():bool
    {
        return $this->uri->getScheme() === 'https';
    }

    /**
     * getPostParams
     *
     *
     * @return array
     */
    public function getPostParams():array
    {
        return $this->post;
    }

    /**
     * getPost
     *
     * @param string $name    Key
     * @param mixed  $default Default value if not found
     *
     * @return mixed
     */
    public function getPost($name, $default = null)
    {
        return isset($this->post[$name]) ? $this->post[$name] : $default;
    }

    /**
     * @param string $name Key name
     *
     * @return bool
     */
    public function hasPost($name):bool
    {
        return array_key_exists($name, $this->post);
    }

    /**
     * @param string $name    Key name
     * @param mixed  $default Default value if not found
     *
     * @return mixed|null
     */
    public function getQuery($name, $default = null)
    {
        return isset($this->query[$name]) ? $this->query[$name] : $default;
    }

    /**
     * @param string $name Key name
     *
     * @return bool
     */
    public function hasQuery($name):bool
    {
        return array_key_exists($name, $this->query);
    }

    /**
     * @param string $name File name
     *
     * @return UploadedFile
     */
    public function getFile($name):UploadedFile
    {
        return isset($this->files[$name]) ? $this->files[$name] : new UploadedFile([]);
    }

    /**
     * @param string $name File name
     *
     * @return bool
     */
    public function hasFile($name):bool
    {
        return array_key_exists($name, $this->files);
    }

    /**
     * @param string $attribute Attribute name
     * @param mixed  $default   Default value if not found
     *
     * @return mixed|null
     */
    public function getServer($attribute, $default = null)
    {
        return isset($this->server[$attribute]) ? $this->server[$attribute] : $default;
    }

    /**
     * @return array|UploadedFile[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @return array
     */
    public function getInputParams()
    {
        $input = $this->getInput();
        $type  = array_flip($this->getHeader('content-type'));
        if (isset($type['application/json'])) {
            return json_decode($input, true);
        }
        else if (isset($type['application/x-www-form-urlencoded'])) {
            parse_str($input, $params);
            return $params;
        }

        return [$input];
    }

    /**
     * Get PHP input
     *
     * @return mixed
     */
    public function getInput()
    {
        return file_get_contents('php://input');
    }

    /**
     * Search through all input globals
     *
     * @param string $name Key name
     *
     * @return bool
     */
    public function hasInput($name):bool
    {
        return array_key_exists($name, $this->getInputParams());
    }

    /*
     * Client Functions
     */

    /**
     * @return string
     */
    public function getClientIp():string
    {
        return (string)$this->getServer('HTTP_X_FORWARDED_FOR', $this->getServer('HTTP_X_REAL_IP', $this->getServer('REMOTE_ADDR')));
    }

    /**
     * @return string
     */
    public function getClientUserAgent():string
    {
        return (string)$this->getServer('HTTP_USER_AGENT');
    }
}
