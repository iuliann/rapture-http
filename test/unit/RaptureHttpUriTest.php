<?php

class RaptureHttpUriTest extends \PHPUnit_Framework_TestCase
{
    public function testUri()
    {
        $url = 'http://user:pass@dev.ro:80/path/to/nowhere?foo=bar#fragment';

        $uri = new \Rapture\Http\Uri($url);

        $this->assertEquals('http', $uri->getScheme());
        $this->assertEquals('user:pass@dev.ro:80', $uri->getAuthority());
        $this->assertEquals('user:pass', $uri->getUserInfo());
        $this->assertEquals('user', $uri->getUser());
        $this->assertEquals('pass', $uri->getPass());
        $this->assertEquals('dev.ro', $uri->getHost());
        $this->assertEquals(80, $uri->getPort());
        $this->assertEquals('/path/to/nowhere', $uri->getPath());
        $this->assertEquals('foo=bar', $uri->getQuery());
        $this->assertEquals('fragment', $uri->getFragment());
        $this->assertEquals('nowhere', $uri->getSegment(2));
        $this->assertEquals(['foo' => 'bar'], $uri->getQueryParams());
        $this->assertEquals($url, (string)$uri);
    }

    public function testSerialize()
    {
        $url = 'http://user:pass@dev.ro:80/path/to/nowhere?foo=bar#fragment';

        $uri = new \Rapture\Http\Uri($url);

        $deSerialized = unserialize(serialize($uri));

        $this->assertEquals('http', $deSerialized->getScheme());
        $this->assertEquals('user:pass@dev.ro:80', $deSerialized->getAuthority());
        $this->assertEquals('user:pass', $deSerialized->getUserInfo());
        $this->assertEquals('user', $deSerialized->getUser());
        $this->assertEquals('pass', $deSerialized->getPass());
        $this->assertEquals('dev.ro', $deSerialized->getHost());
        $this->assertEquals(80, $deSerialized->getPort());
        $this->assertEquals('/path/to/nowhere', $deSerialized->getPath());
        $this->assertEquals('foo=bar', $deSerialized->getQuery());
        $this->assertEquals('fragment', $deSerialized->getFragment());
        $this->assertEquals('nowhere', $deSerialized->getSegment(2));
        $this->assertEquals(['foo' => 'bar'], $deSerialized->getQueryParams());
        $this->assertEquals($url, (string)$deSerialized);
    }

    public function testMutator()
    {
        $url = 'http://user:pass@dev.ro:80/path/to/nowhere?foo=bar#fragment';

        $uri = (new \Rapture\Http\Uri($url))
            ->withScheme('https')
            ->withUser('username')
            ->withPass('password')
            ->withHost('host.com')
            ->withPort(8080)
            ->withPath('/my/new/path')
            ->withQuery('q=search')
            ->withFragment('newFragment');

        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('username' ,$uri->getUser());
        $this->assertEquals('password' ,$uri->getPass());
        $this->assertEquals('host.com' ,$uri->getHost());
        $this->assertEquals(8080 ,$uri->getPort());
        $this->assertEquals('/my/new/path' ,$uri->getPath());
        $this->assertEquals('q=search' ,$uri->getQuery());
        $this->assertEquals('newFragment' ,$uri->getFragment());
    }

    public function testQueryParamsMutator()
    {
        $url = 'http://user:pass@dev.ro:80/path/to/nowhere?foo=bar#fragment';
        $uri = new \Rapture\Http\Uri($url);

        $this->assertEquals('q=search', $uri->withQueryParams(['q' => 'search'])->getQuery());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHostLengthException()
    {
        $uri = new \Rapture\Http\Uri('/path');
        $host = 'hosthosthosthosthosthosthosthosthosthosthosthosthosthosthosthosthosthosthosthosthosthosthosthosthost';
        $host .= $host;
        $host .= $host;
        $uri->withHost($host);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHostNameException()
    {
        $uri = new \Rapture\Http\Uri('/path');
        $uri->withHost('host|hack');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPortException()
    {
        $uri = new \Rapture\Http\Uri('/path');
        $uri->withPort(19);
    }
}
