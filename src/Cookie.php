<?php

namespace Rapture\Http;

/**
 * Cookie
 *
 * @package Rapture\Http
 * @author  Iulian N. <rapture@iuliann.ro>
 * @license LICENSE MIT
 */
class Cookie
{
    /** @var string */
    protected $name;

    /** @var mixed */
    protected $value;

    /** @var int */
    protected $expire;

    /** @var string */
    protected $path;

    /** @var string */
    protected $domain;

    /** @var bool */
    protected $isSecure;

    /** @var bool */
    protected $isHttpOnly;

    /**
     * @param string $name       Cookie name
     * @param mixed  $value      Cookie value
     * @param int    $expire     Cookie expiration
     * @param string $path       Cookie path
     * @param string $domain     Cookie domain
     * @param bool   $isSecure   HTTPS cookie
     * @param bool   $isHttpOnly HTTP cookie
     */
    public function __construct($name, $value = null, $expire = 0, $path = '/', $domain = null, $isSecure = false, $isHttpOnly = true)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Cookie name cannot be empty.');
        }

        if ($expire instanceof \DateTime) {
            $expire = $expire->format('U');
        } elseif (!is_numeric($expire)) {
            $expire = strtotime($expire);

            if ($expire === false) {
                throw new \InvalidArgumentException('Cookie expire is not valid.');
            }
        }

        $this->name = $name;
        $this->value = $value;
        $this->expire = $expire;
        $this->path = $path ?: '/';
        $this->domain = $domain;
        $this->isSecure = (bool)$isSecure;
        $this->isHttpOnly = (bool)$isHttpOnly;
    }

    /**
     * getName
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * getValue
     *
     * @return string
     */
    public function getValue()
    {
        return $this->name;
    }

    /**
     * getExpire
     *
     * @return int|string
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * getPath
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * getDomain
     *
     * @return null|string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * isSecure
     *
     * @return bool
     */
    public function isSecure()
    {
        return $this->isSecure;
    }

    /**
     * isHttpOnly
     *
     * @return bool
     */
    public function isHttpOnly()
    {
        return $this->isHttpOnly;
    }
}
