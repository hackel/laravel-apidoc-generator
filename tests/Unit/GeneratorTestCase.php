<?php

namespace Mpociot\ApiDoc\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Mpociot\ApiDoc\Generators\LaravelGenerator;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;

abstract class GeneratorTestCase extends TestCase
{
    /**
     * @var \Mpociot\ApiDoc\Generators\AbstractGenerator
     */
    protected $generator;

    protected function getPackageProviders($app)
    {
        return [
            ApiDocGeneratorServiceProvider::class,
        ];
    }

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->generator = new LaravelGenerator();
    }

    /** @test */
    public function can_parse_endpoint_description()
    {
        $route = $this->createRoute('GET', '/api/test', 'withEndpointDescription');
        $parsed = $this->generator->processRoute($route);

        $this->assertSame('Example title.', $parsed['title']);
        $this->assertSame("This will be the long description.\nIt can also be multiple lines long.", $parsed['description']);
    }

    /** @test */
    public function can_parse_body_parameters()
    {
        $route = $this->createRoute('GET', '/api/test', 'withBodyParameters');
        $parameters = $this->generator->processRoute($route)['parameters'];

        $this->assertArraySubset([
            'user_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'The id of the user.',
            ],
            'room_id' => [
                'type' => 'string',
                'required' => false,
                'description' => 'The id of the room.',
            ],
            'forever' => [
                'type' => 'boolean',
                'required' => false,
                'description' => 'Whether to ban the user forever.',
            ],
            'another_one' => [
                'type' => 'number',
                'required' => false,
                'description' => 'Just need something here.',
            ],
            'yet_another_param' => [
                'type' => 'object',
                'required' => true,
                'description' => '',
            ],
            'even_more_param' => [
                'type' => 'array',
                'required' => false,
                'description' => '',
            ],
        ], $parameters);
    }

    /** @test */
    public function can_parse_route_group()
    {
        $route = $this->createRoute('GET', '/api/test', 'dummy');
        $routeGroup = $this->generator->processRoute($route)['group'];

        $this->assertSame('Group A', $routeGroup);
    }

    /** @test */
    public function method_can_override_controller_group()
    {
        $route = $this->createRoute('GET', '/api/test', 'withGroupOverride');
        $routeGroup = $this->generator->processRoute($route)['group'];

        $this->assertSame('Group B', $routeGroup);
    }

    /** @test */
    public function can_parse_auth_tags()
    {
        $route = $this->createRoute('GET', '/api/test', 'withAuthenticatedTag');
        $authenticated = $this->generator->processRoute($route)['authenticated'];
        $this->assertTrue($authenticated);

        $route = $this->createRoute('GET', '/api/test', 'dummy');
        $authenticated = $this->generator->processRoute($route)['authenticated'];
        $this->assertFalse($authenticated);
    }

    /** @test */
    public function can_parse_route_methods()
    {
        $route = $this->createRoute('GET', '/get', 'withEndpointDescription');
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['GET'], $parsed['methods']);

        $route = $this->createRoute('POST', '/post', 'withEndpointDescription');
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['POST'], $parsed['methods']);

        $route = $this->createRoute('PUT', '/put', 'withEndpointDescription');
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['PUT'], $parsed['methods']);

        $route = $this->createRoute('DELETE', '/delete', 'withEndpointDescription');
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['DELETE'], $parsed['methods']);
    }

    /** @test */
    public function can_parse_response_tag()
    {
        $route = $this->createRoute('POST', '/responseTag', 'withResponseTag');

        $parsed = $this->generator->processRoute($route);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertJsonStringEqualsJsonString(json_encode([
            'id' => 4,
            'name' => 'banana',
            'color' => 'red',
            'weight' => '1 kg',
            'delicious' => true,
        ]), $parsed['response']);
    }

    /** @test */
    public function can_parse_transformer_tag()
    {
        $route = $this->createRoute('GET', '/transformerTag', 'transformerTag');
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":{"id":1,"description":"Welcome on this test versions","name":"TestName"}}'
        );
    }

    /** @test */
    public function can_parse_transformer_tag_with_model()
    {
        $route = $this->createRoute('GET', '/transformerTagWithModel', 'transformerTagWithModel');
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":{"id":1,"description":"Welcome on this test versions","name":"TestName"}}'
        );
    }

    /** @test */
    public function can_parse_transformer_collection_tag()
    {
        $route = $this->createRoute('GET', '/transformerCollectionTag', 'transformerCollectionTag');
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":[{"id":1,"description":"Welcome on this test versions","name":"TestName"},'.
            '{"id":1,"description":"Welcome on this test versions","name":"TestName"}]}'
        );
    }

    /** @test */
    public function can_parse_transformer_collection_tag_with_model()
    {
        $route = $this->createRoute('GET', '/transformerCollectionTagWithModel', 'transformerCollectionTagWithModel');
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":[{"id":1,"description":"Welcome on this test versions","name":"TestName"},'.
            '{"id":1,"description":"Welcome on this test versions","name":"TestName"}]}'
        );
    }

    abstract public function createRoute(string $httpMethod, string $path, string $controllerMethod);
}
