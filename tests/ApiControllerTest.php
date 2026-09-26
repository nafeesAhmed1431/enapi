<?php

namespace NafeesAhmed\Enapi\Tests;

use NafeesAhmed\Enapi\ApiController;
use PHPUnit\Framework\TestCase;

class ApiControllerTest extends TestCase
{
    public function testGetRoute(): void
    {
        $api = new ApiController();
        $api->get('/users', fn () => ['users' => []]);

        $routes = $api->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertSame('GET', $routes[0]['method']);
        $this->assertSame('/users', $routes[0]['path']);
    }

    public function testPostRoute(): void
    {
        $api = new ApiController();
        $api->post('/users', fn () => ['status' => 'created']);

        $routes = $api->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertSame('POST', $routes[0]['method']);
    }

    public function testHandleRequestMatchesRouteAndParams(): void
    {
        $api = new ApiController();
        $api->get('/users/{id}', fn () => ['id' => $_GET['id'] ?? null]);

        $result = $api->handleRequest('GET', '/users/42');
        $this->assertSame(['id' => '42'], $result);
    }

    public function testHandleRequestReturns404ForUnmatchedRoute(): void
    {
        $api = new ApiController();
        $api->get('/users', fn () => ['users' => []]);

        $result = $api->handleRequest('GET', '/nope');
        $this->assertFalse($result['success']);
        $this->assertSame('Not Found', $result['error']);
    }

    public function testMiddlewareCanBlockRequest(): void
    {
        $api = new ApiController();
        $api->middleware(fn () => false);
        $api->get('/users', fn () => ['users' => []]);

        $result = $api->handleRequest('GET', '/users');
        $this->assertFalse($result['success']);
        $this->assertSame('Forbidden', $result['error']);
    }

    public function testMiddlewareAllowsRequestWhenReturningTrue(): void
    {
        $api = new ApiController();
        $ran = false;
        $api->middleware(function () use (&$ran) {
            $ran = true;
            return true;
        });
        $api->get('/users', fn () => ['users' => []]);

        $result = $api->handleRequest('GET', '/users');
        $this->assertTrue($ran);
        $this->assertSame(['users' => []], $result);
    }
}
