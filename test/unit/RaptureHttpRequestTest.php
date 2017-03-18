<?php

use Rapture\Http\Request;
use Rapture\Http\Uri;

class RaptureHttpRequestTest extends \PHPUnit_Framework_TestCase
{
    public function testParseHeaders()
    {
        $headers = [
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_HOST' => 'domain.com',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_PRAGMA' => 'no-cache',
            'HTTP_CACHE_CONTROL' => 'no-cache',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
        ];

        $this->assertEquals([
            'Host' => ['domain.com'],
            'Connection' => ['keep-alive'],
            'Pragma' => ['no-cache'],
            'Cache-Control' => ['no-cache'],
            'Accept' => ['text/html','application/xhtml+xml','application/xml;q=0.9','image/webp','*/*;q=0.8'],
            'Upgrade-Insecure-Requests' => [1]
        ], Request::parseHeaders($headers));
    }

    public function testParseFiles()
    {
        $files = array(
            'files' => array(
                array(
                    'tmp_name' => 'phpUxcOty',
                    'name' => 'my-file.png',
                    'size' => 90996,
                    'type' => 'image/png',
                    'error' => 0,
                ),
                array(
                    'tmp_name' => 'phpZmsTRS',
                    'name' => 'my-second-file.png',
                    'size' => 90996,
                    'type' => 'image/png',
                    'error' => 0,
                ),
            ),
        );

        $parseFiles = Request::parseFiles($files);

        $this->assertEquals('image/png', $parseFiles['files'][0]->getClientMediaType());
        $this->assertEquals('my-second-file.png', $parseFiles['files'][1]->getClientFilename());
    }

    public function testProtocolVersion()
    {
        $request = new Request();

        $this->assertEquals('1.1', $request->getProtocolVersion());

        $this->assertEquals('2.0', $request->withProtocolVersion('2.0')->getProtocolVersion());
    }

    public function testHeaders()
    {
        $headers = [
            'Host' => ['domain.com'],
            'Connection' => ['keep-alive'],
            'Pragma' => ['no-cache'],
            'Cache-Control' => ['no-cache'],
            'Accept' => ['text/html','application/xhtml+xml','application/xml;q=0.9','image/webp','*/*;q=0.8'],
            'Upgrade-Insecure-Requests' => [1]
        ];

        $request = new Request(null, Request::METHOD_GET, $headers);

        $this->assertEquals($headers, $request->getHeaders());
        $this->assertEquals(['domain.com'], $request->getHeader('Host'));
        $this->assertEquals('text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8', $request->getHeaderLine('Accept'));

        $request->withHeader('Host', 'new-domain.com');
        $this->assertEquals(['new-domain.com'], $request->getHeader('Host'));
        $this->assertTrue($request->hasHeader('Host'));

        $request->withAddedHeader('Accept', 'image/png');
        $this->assertEquals(
            ['text/html','application/xhtml+xml','application/xml;q=0.9','image/webp','*/*;q=0.8', 'image/png'],
            $request->getHeader('Accept')
        );

        $request->withAddedHeader('Test', 'yes');
        $this->assertEquals(['yes'], $request->getHeader('Test'));

        $request->withoutHeader('Test');
        $this->assertFalse($request->hasHeader('Test'));
    }

    public function testBody()
    {
        $request = new Request();

        $stream = new \Rapture\Http\Stream(fopen('php://temp', 'r+'));
        $stream->write('test');
        $stream->rewind();

        $request->withBody($stream);
        $this->assertEquals('test', $request->getBody()->getContents());
    }

    public function testMethod()
    {
        $request = new Request();

        $this->assertEquals(Request::METHOD_GET, $request->getMethod());
        $this->assertEquals(Request::METHOD_POST, $request->withMethod(Request::METHOD_POST)->getMethod());
    }

    public function testUri()
    {
        $request = new Request();
        $this->assertEquals('/', $request->getUri()->getPath());

        $request->withUri(new Uri('http://dev.com/test'));
        $this->assertEquals('dev.com', $request->getUri()->getHost());

        $request->withUri(new Uri('http://dev2.com'), true);
        $this->assertEquals('dev2.com', $request->getUri()->getHost());
    }

    public function testGlobals()
    {
        $request = new Request(null, Request::METHOD_GET, [], [], [
            'server' => ['Host' => 'domain.com'],
            'query' => ['q' => 'search'],
            'post' => ['foo' => 'bar'],
            'files' => ['file' => [
                'tmp_name' => 'php543jd',
                'name' => 'file.png',
                'size' => 20,
                'type' => 'image/png',
                'error' => 0,
            ]]
        ]);

        $this->assertEquals(['Host' => 'domain.com'], $request->getServerParams());
        $this->assertEquals(['q' => 'search'], $request->getQueryParams());
        $this->assertEquals(['foo' => 'bar'], $request->getPostParams());
        $this->assertEquals('search', $request->getQuery('q'));
        $this->assertEquals('bar', $request->getPost('foo'));

        $this->assertEquals('file.png', $request->getUploadedFiles()['file']->getClientFilename());

        $request->withUploadedFiles([
            'file' => [
                'tmp_name' => 'php543jd',
                'name' => 'file.png',
                'size' => 30,
                'type' => 'image/png',
                'error' => 0,
            ]
        ]);
        $this->assertEquals(30, $request->getUploadedFiles()['file']->getSize());
        $this->assertEquals('image/png', $request->getFile('file')->getClientMediaType());
    }

    public function testIsMethod()
    {
        $request = new Request();

        $this->assertTrue($request->isGet());
        $this->assertFalse($request->isPost());
        $this->assertFalse($request->isPut());
        $this->assertFalse($request->isPatch());
        $this->assertFalse($request->isHead());
        $this->assertFalse($request->isOptions());
        $this->assertFalse($request->isDelete());
        $this->assertFalse($request->isAjax());
        $this->assertFalse($request->isSecure());
        $this->assertFalse($request->isPurge());
        $this->assertFalse($request->isTrace());
        $this->assertFalse($request->isConnect());
    }

    public function testAttributes()
    {
        $request = new Request(new Uri('/'), Request::METHOD_GET, [], ['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $request->getAttributes());
        $this->assertEquals('bar', $request->getAttribute('foo'));
        $this->assertEquals('baz', $request->withAttribute('foo', 'baz')->getAttribute('foo'));
        $this->assertEquals('baa', $request->withoutAttribute('foo')->getAttribute('foo', 'baa'));
    }
}
