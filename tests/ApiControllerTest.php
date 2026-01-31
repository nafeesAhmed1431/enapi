<?php

namespace NafeesAhmed\Enapi\Tests;

use NafeesAhmed\Enapi\ApiController;

class ApiControllerTest
{
    public function testGetRoute()
    {
        $api = new ApiController();
        $api->get('/users', function() {
            return ['users' => []];
        });

        $routes = $api->getRoutes();
        assert(count($routes) === 1);
        assert($routes[0]['method'] === 'GET');
        assert($routes[0]['path'] === '/users');
    }

    public function testPostRoute()
    {
        $api = new ApiController();
        $api->post('/users', function() {
            return ['status' => 'created'];
        });

        $routes = $api->getRoutes();
        assert(count($routes) === 1);
        assert($routes[0]['method'] === 'POST');
    }
}
