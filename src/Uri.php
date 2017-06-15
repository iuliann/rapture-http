<?php

namespace Rapture\Http;

use Psr\Http\Message\UriInterface;

/**
 * Class Uri
 *
 * @package Rapture\Http
 */
class Uri implements UriInterface
{
    /** @var array */
    protected $data = [
        'scheme'   => 'http',
        'host'     => 'localhost',
        'port'     => '',
        'user'     => '',
        'pass'     => '',
        'path'     => '/',
        'query'    => '',
        'fragment' => '',
    ];

    /**
     * Uri constructor.
     *
     * @param string $url URI
     */
    public function __construct(string $url)
    {
        $this->data = (array)parse_url($url) + $this->data;
    }

    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme()
    {
        return $this->data['scheme'];
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority()
    {
        return (isset($this->data['user'][0]) ? $this->getUserInfo() . '@' : '')
        . $this->getHost()
        . ($this->getPort() ? ':' . $this->getPort() : '');
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo()
    {
        return $this->data['user'] . (isset($this->data['pass'][0]) ? ':' . $this->data['pass'] : '');
    }

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost()
    {
        return $this->data['host'];
    }

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
     */
    public function getPort()
    {
        return (int)$this->data['port'];
    }

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath()
    {
        return $this->data['path'];
    }

    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQuery()
    {
        return $this->data['query'];
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return $this->data['fragment'];
    }

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     *
     * @return self A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme)
    {
        return (clone $this)->withData('scheme', $scheme);
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string      $user     The user name to use for authority.
     * @param null|string $password The password associated with $user.
     *
     * @return self A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null)
    {
        return (clone $this)->withData('user', $user)->withData('pass', $password);
    }

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     *
     * @return self A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host)
    {
        if (strlen($host) > 63) {
            throw new \InvalidArgumentException('Host name is too long');
        }

        if (!preg_match('/^[a-z0-9\-\.]+$/', $host)) {
            throw new \InvalidArgumentException('Invalid name for host');
        }

        return (clone $this)->withData('host', $host);
    }

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *                       removes the port information.
     *
     * @return self A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port)
    {
        $port = (int)$port;
        if ($port < 20 || $port > 65535) {
            throw new \InvalidArgumentException('Invalid port number');
        }

        return (clone $this)->withData('port', (int)$port);
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     *
     * @return self A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
        return (clone $this)->withData('path', '/' . trim($path, '/'));
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     *
     * @return self A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
        return (clone $this)->withData('query', $query);
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     *
     * @return self A new instance with the specified fragment.
     */
    public function withFragment($fragment)
    {
        return (clone $this)->withData('fragment', $fragment);
    }

    /**
     * Return the string representation as a URI reference.
     *
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters. But there are two
     *   cases where the path has to be adjusted to make the URI reference
     *   valid as PHP does not allow to throw an exception in __toString():
     *     - If the path is rootless and an authority is present, the path MUST
     *       be prefixed by "/".
     *     - If the path is starting with more than one "/" and no authority is
     *       present, the starting slashes MUST be reduced to one.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string
     */
    public function __toString()
    {
        return ($this->getScheme() . '://')
        . $this->getAuthority()
        . $this->getPath()
        . ($this->getQuery() ? '?' . $this->getQuery() : '')
        . ($this->getFragment() ? '#' . $this->getFragment() : '');
    }

    /*
     * Extra
     */

    /**
     * Mutable
     *
     * @param string $key   Data key
     * @param string $value Data value
     *
     * @return $this
     */
    public function withData(string $key, string $value)
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * withUser
     *
     * @param string $user Username
     *
     * @return $this
     */
    public function withUser($user)
    {
        return (clone $this)->withData('user', $user);
    }

    /**
     * withPass
     *
     * @param string $pass Password
     *
     * @return $this
     */
    public function withPass($pass)
    {
        return (clone $this)->withData('pass', $pass);
    }

    /**
     * withQueryParams
     *
     * @param array $params Query params
     *
     * @return $this
     */
    public function withQueryParams(array $params)
    {
        return (clone $this)->withData('query', http_build_query($params));
    }

    /**
     * withQueryParam
     *
     * @param string $key   Key
     * @param mixed  $value Value
     *
     * @return $this
     */
    public function withQueryParam($key, $value)
    {
        $params = $this->getQueryParams();
        $params[$key] = $value;

        return $this->withQueryParams($params);
    }

    /**
     * getUser
     *
     * @return string|null
     */
    public function getUser()
    {
        return $this->data['user'];
    }

    /**
     * getPass
     *
     * @return string|null
     */
    public function getPass()
    {
        return $this->data['pass'];
    }

    /**
     * getSegment
     *
     * @param int $index Segment index
     *
     * @return string
     */
    public function getSegment($index = 0)
    {
        $segments = explode('/', trim($this->getPath(), '/'));

        return $segments[$index] ?: null;
    }

    /**
     * Get last segment from path
     *
     * @return string
     */
    public function getLastSegment()
    {
        $segments = explode('/', trim($this->getPath(), '/')) + [''];

        return array_pop($segments);
    }

    /**
     * getQueryParams
     *
     * @return array
     */
    public function getQueryParams()
    {
        parse_str($this->data['query']);

        return get_defined_vars();
    }

    /**
     * @param string $name Param name
     *
     * @return mixed
     */
    public function getQueryParam(string $name)
    {
        return $this->getQueryParams()[$name] ?? null;
    }

    /**
     * @param string $name Param name
     *
     * @return bool
     */
    public function hasQueryParam(string $name)
    {
        return array_key_exists($name, $this->getQueryParams());
    }

    /**
     * toString
     *
     * Format:
     * $s: scheme
     * $h: host
     * $a: user:pass
     * $p: path
     * $q: query
     * $f: fragment
     *
     * @param string $format Format string
     *
     * @return string
     */
    public function format($format = '$p$q')
    {
        $replacements = [
            '$s' => $this->getScheme(),
            '$h' => $this->getHost(),
            '$a' => $this->getAuthority(),
            '$p' => $this->getPath(),
            '$q' => '?' . $this->getQuery(),
            '$f' => '#' . $this->getFragment(),
        ];

        return str_replace(array_keys($replacements), $replacements, $format);
    }
}
