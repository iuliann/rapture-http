<?php


use Rapture\Http\Request;
use Rapture\Http\Uri;

class RaptureHttpClientTest extends \PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $request = new Request(new Uri('https://google.ro'), Request::METHOD_GET);

        $client = new \Rapture\Http\Client([
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = $client->sendRequest($request);

        $this->assertEquals(\Rapture\Http\Response::STATUS_OK, $response->getStatusCode());
    }
}
