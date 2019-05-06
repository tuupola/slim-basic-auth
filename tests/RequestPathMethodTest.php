<?php

namespace Slim\Middleware\HttpBasicAuthentication;


use Zend\Diactoros\Request;
use Zend\Diactoros\Uri;

class RequestPathMethodTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldNotAuthenticateGet()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");


        $rule = new RequestPathMethodRule([
            'path' => [
                '/api/*' => [
                    'post'
                ]
            ]
        ]);

        $this->assertFalse($rule($request));
    }

    public function testShouldNotAuthenticatePost()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");

        $rule = new RequestPathMethodRule([
            'path' => [
                '/api/*' => [
                    'get'
                ]
            ]
        ]);

        $this->assertTrue($rule($request));
    }

    public function testShouldNotAuthenticatePassthrough()
    {
        $requestOne = (new Request())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");

        $requestTwo = (new Request())
            ->withUri(new Uri("https://example.com/api/addlog"))
            ->withMethod('POST');

        $requestThree = (new Request())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("POST");

        $rule = new RequestPathMethodRule([
            'path' => [
                '/api/*' => [
                    'get',
                    'post'
                ]
            ],
            'passthrough' => [
                '/api/addlog' => [
                    'post'
                ]
            ]
        ]);

        $this->assertTrue($rule($requestOne));
        $this->assertFalse($rule($requestTwo));
        $this->assertTrue($rule($requestThree));
    }
}