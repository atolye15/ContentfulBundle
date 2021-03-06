<?php

/**
 * This file is part of the contentful/contentful-bundle package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace Atolye15\Tests\ContentfulBundle\Unit\DependencyInjection;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Atolye15\ContentfulBundle\DependencyInjection\ContentfulExtension;
use Atolye15\Delivery\Client;
use Atolye15\Delivery\Client\ClientInterface;
use Atolye15\Tests\ContentfulBundle\TestCase;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

class ContentfulExtensionTest extends TestCase
{
    public function testContainerCompiles()
    {
        $container = $this->getContainer();
        $extension = new ContentfulExtension();

        $container->registerExtension($extension);

        $container->set(CacheItemPoolInterface::class, new ArrayCachePool(\null));
        $container->set(LoggerInterface::class, new Logger('test', [new TestHandler()]));
        $container->set('app.http_client', new HttpClient());

        $extension->load([
            'contentful' => [
                'delivery' => [
                    'main' => [
                        'default' => \true,
                        'token' => 'b4c0n73n7fu1',
                        'space' => 'cfexampleapi',
                        'options' => [
                            'logger' => \true,
                            'client' => 'app.http_client',
                            'cache' => [
                                'pool' => \true,
                            ],
                        ],
                    ],
                ],
            ],
        ], $container);

        $container->compile();
        $this->assertTrue($container->isCompiled());
    }

    public function testLoad()
    {
        $container = $this->getContainer();
        $extension = new ContentfulExtension();

        $container->registerExtension($extension);

        $extension->load([
            'contentful' => [
                'delivery' => [
                    'main' => [
                        'token' => 'aaa',
                        'space' => 'ccc',
                        'options' => [
                            'logger' => \null,
                            'cache' => [
                                'pool' => \null,
                            ],
                        ],
                    ],
                ],
            ],
        ], $container);

        $definition = $container->getDefinition('contentful.delivery.main_client');
        $this->assertTrue($definition->isAutowired());

        $client = $container->get('contentful.delivery.main_client');

        // Makes sure that getting the same client twice will not trigger the factory multiple times
        $this->assertSame(
            $client,
            $container->get('contentful.delivery.main_client')
        );

        $this->assertSame($client, $container->get(ClientInterface::class));
        $this->assertSame($client, $container->get(Client::class));
        $this->assertSame($client, $container->get('contentful.delivery.client'));
    }

    public function testEmptyConfigDoesNotThrowErrors()
    {
        $container = $this->getContainer();
        $extension = new ContentfulExtension();

        $container->registerExtension($extension);

        $extension->load([], $container);

        $this->markTestAsPassed('Test did not throw an exception');
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "contentful.delivery.main.options.host": Parameter "host" in client configuration must be a valid URL.
     */
    public function testInvalidHost()
    {
        $container = $this->getContainer();
        $extension = new ContentfulExtension();

        $container->registerExtension($extension);

        $extension->load([
            'contentful' => [
                'delivery' => [
                    'main' => [
                        'token' => 'aaa',
                        'space' => 'ccc',
                        'options' => [
                            'host' => 'invalid-url',
                        ],
                    ],
                ],
            ],
        ], $container);
    }

    public function testInvalidNumberOfDefaultClients()
    {
        $container = $this->getContainer();
        $extension = new ContentfulExtension();

        $container->registerExtension($extension);

        try {
            $extension->load([
                'contentful' => [
                    'delivery' => [
                        'main' => [
                            'token' => 'aaa',
                            'space' => 'bbb',
                        ],
                        'preview' => [
                            'token' => 'aaa',
                            'space' => 'bbb',
                        ],
                    ],
                ],
            ], $container);

            $this->fail('Configuration did not throw an exception with no default client');
        } catch (LogicException $exception) {
            $this->assertSame(
                'Contentful client configuration requires exactly one client defined with "default: true" key, 0 found.',
                $exception->getMessage()
            );
        }

        try {
            $extension->load([
                'contentful' => [
                    'delivery' => [
                        'main' => [
                            'default' => \true,
                            'token' => 'aaa',
                            'space' => 'bbb',
                        ],
                        'preview' => [
                            'default' => \true,
                            'token' => 'aaa',
                            'space' => 'bbb',
                        ],
                    ],
                ],
            ], $container);

            $this->fail('Configuration did not throw an exception with multiple default clients');
        } catch (LogicException $exception) {
            $this->assertSame(
                'Contentful client configuration requires exactly one client defined with "default: true" key, 2 found.',
                $exception->getMessage()
            );
        }
    }

    public function testGetClientWithDefaultCache()
    {
        $container = $this->getContainer();
        $extension = new ContentfulExtension();

        $container->registerExtension($extension);

        $items = [];
        $cache = new ArrayCachePool(\null, $items);
        $container->set(CacheItemPoolInterface::class, $cache);

        $extension->load([
            'contentful' => [
                'delivery' => [
                    'main' => [
                        'default' => \true,
                        'token' => 'b4c0n73n7fu1',
                        'space' => 'cfexampleapi',
                        'options' => [
                            'logger' => \null,
                            'cache' => [
                                'pool' => \true,
                                'runtime' => \true,
                                'content' => \true,
                            ],
                        ],
                    ],
                ],
            ],
        ], $container);

        /** @var Client $client */
        $client = $container->get(Client::class);
        $this->assertInstanceOf(Client::class, $client);

        $client->getSpace();

        $this->assertArrayHasKey('contentful.DELIVERY.cfexampleapi.master.Space.cfexampleapi.__ALL__', $items);
        $this->assertJson($items['contentful.DELIVERY.cfexampleapi.master.Space.cfexampleapi.__ALL__'][0]);

        // Cache clearer
        $this->assertTrue($container->hasDefinition('contentful.delivery.main_client.cache_clearer'));
        $definition = $container->getDefinition('contentful.delivery.main_client.cache_clearer');
        $this->assertTrue($definition->hasTag('kernel.cache_clearer'));

        $arguments = $definition->getArguments();
        /** @var Reference $client */
        $client = $arguments[0];
        $this->assertInstanceOf(Reference::class, $client);
        $this->assertSame('contentful.delivery.main_client', (string) $client);
        /** @var Reference $cachePool */
        $cachePool = $arguments[1];
        $this->assertInstanceOf(Reference::class, $cachePool);
        $this->assertSame(CacheItemPoolInterface::class, (string) $cachePool);

        // Cache warmer
        $this->assertTrue($container->hasDefinition('contentful.delivery.main_client.cache_warmer'));
        $definition = $container->getDefinition('contentful.delivery.main_client.cache_warmer');
        $this->assertTrue($definition->hasTag('kernel.cache_warmer'));

        $arguments = $definition->getArguments();
        /** @var Reference $client */
        $client = $arguments[0];
        $this->assertInstanceOf(Reference::class, $client);
        $this->assertSame('contentful.delivery.main_client', (string) $client);
        /** @var Reference $cachePool */
        $cachePool = $arguments[1];
        $this->assertInstanceOf(Reference::class, $cachePool);
        $this->assertSame(CacheItemPoolInterface::class, (string) $cachePool);
        /** @var bool $runtime */
        $runtime = $arguments[2];
        $this->assertInternalType('bool', $runtime);
        $this->assertTrue($runtime);
        /** @var bool $content */
        $content = $arguments[3];
        $this->assertInternalType('bool', $content);
        $this->assertTrue($content);
    }

    public function testGetClientWithCustomCache()
    {
        $container = $this->getContainer();
        $extension = new ContentfulExtension();

        $container->registerExtension($extension);

        $items = [];
        $cache = new ArrayCachePool(\null, $items);
        $container->set(ArrayCachePool::class, $cache);

        $extension->load([
            'contentful' => [
                'delivery' => [
                    'main' => [
                        'default' => \true,
                        'token' => 'b4c0n73n7fu1',
                        'space' => 'cfexampleapi',
                        'options' => [
                            'logger' => \null,
                            'cache' => [
                                'pool' => ArrayCachePool::class,
                                'runtime' => \true,
                                'content' => \true,
                            ],
                        ],
                    ],
                ],
            ],
        ], $container);

        /** @var Client $client */
        $client = $container->get(Client::class);
        $this->assertInstanceOf(Client::class, $client);

        $client->getSpace();

        $this->assertArrayHasKey('contentful.DELIVERY.cfexampleapi.master.Space.cfexampleapi.__ALL__', $items);
        $this->assertJson($items['contentful.DELIVERY.cfexampleapi.master.Space.cfexampleapi.__ALL__'][0]);

        // Cache clearer
        $this->assertTrue($container->hasDefinition('contentful.delivery.main_client.cache_clearer'));
        $definition = $container->getDefinition('contentful.delivery.main_client.cache_clearer');
        $this->assertTrue($definition->hasTag('kernel.cache_clearer'));

        $arguments = $definition->getArguments();
        /** @var Reference $client */
        $client = $arguments[0];
        $this->assertInstanceOf(Reference::class, $client);
        $this->assertSame('contentful.delivery.main_client', (string) $client);
        /** @var Reference $cachePool */
        $cachePool = $arguments[1];
        $this->assertInstanceOf(Reference::class, $cachePool);
        $this->assertSame(ArrayCachePool::class, (string) $cachePool);

        // Cache warmer
        $this->assertTrue($container->hasDefinition('contentful.delivery.main_client.cache_warmer'));
        $definition = $container->getDefinition('contentful.delivery.main_client.cache_warmer');
        $this->assertTrue($definition->hasTag('kernel.cache_warmer'));

        $arguments = $definition->getArguments();
        /** @var Reference $client */
        $client = $arguments[0];
        $this->assertInstanceOf(Reference::class, $client);
        $this->assertSame('contentful.delivery.main_client', (string) $client);
        /** @var Reference $cachePool */
        $cachePool = $arguments[1];
        $this->assertInstanceOf(Reference::class, $cachePool);
        $this->assertSame(ArrayCachePool::class, (string) $cachePool);
        /** @var bool $runtime */
        $runtime = $arguments[2];
        $this->assertInternalType('bool', $runtime);
        $this->assertTrue($runtime);
        /** @var bool $content */
        $content = $arguments[3];
        $this->assertInternalType('bool', $content);
        $this->assertTrue($content);
    }

    public function testGetClientWithDefaultLogger()
    {
        $container = $this->getContainer();
        $extension = new ContentfulExtension();

        $container->registerExtension($extension);

        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $container->set(LoggerInterface::class, $logger);

        $extension->load([
            'contentful' => [
                'delivery' => [
                    'main' => [
                        'token' => 'b4c0n73n7fu1',
                        'space' => 'cfexampleapi',
                        'options' => [
                            'logger' => \true,
                            'cache' => [
                                'pool' => \null,
                            ],
                        ],
                    ],
                ],
            ],
        ], $container);

        /** @var Client $client */
        $client = $container->get(Client::class);
        $this->assertInstanceOf(Client::class, $client);

        $client->getSpace();

        $logs = $handler->getRecords();
        // 0 INFO space 1 DEBUG space
        $this->assertCount(2, $logs);
        $this->assertSame('INFO', $logs[0]['level_name']);
        $this->assertRegExp('/GET https\:\/\/cdn\.contentful\.com\/spaces\/cfexampleapi \(([0-9]{1,})\.([0-9]{3})s\)/', $logs[0]['message']);
        $this->assertSame('DEBUG', $logs[1]['level_name']);
    }

    public function testGetClientWithCustomLogger()
    {
        $container = $this->getContainer();
        $extension = new ContentfulExtension();

        $container->registerExtension($extension);

        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $container->set(Logger::class, $logger);

        $extension->load([
            'contentful' => [
                'delivery' => [
                    'main' => [
                        'default' => \true,
                        'token' => 'b4c0n73n7fu1',
                        'space' => 'cfexampleapi',
                        'options' => [
                            'logger' => Logger::class,
                            'cache' => [
                                'pool' => \null,
                            ],
                        ],
                    ],
                ],
            ],
        ], $container);

        /** @var Client $client */
        $client = $container->get(Client::class);
        $this->assertInstanceOf(Client::class, $client);

        $client->getSpace();

        $logs = $handler->getRecords();
        // 0 INFO space 1 DEBUG space
        $this->assertCount(2, $logs);
        $this->assertSame('INFO', $logs[0]['level_name']);
        $this->assertRegExp('/GET https\:\/\/cdn\.contentful\.com\/spaces\/cfexampleapi \(([0-9]{1,})\.([0-9]{3})s\)/', $logs[0]['message']);
        $this->assertSame('DEBUG', $logs[1]['level_name']);
    }

    public function testHttpClient()
    {
        $container = $this->getContainer();
        $extension = new ContentfulExtension();

        $container->registerExtension($extension);

        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $hasCalledHandler = \false;
        $stack->push(function (callable $handler) use (&$hasCalledHandler) {
            return function (RequestInterface $request, array $options) use ($handler, &$hasCalledHandler) {
                $hasCalledHandler = \true;

                return $handler($request, $options);
            };
        });
        $container->set('app.http_client', new HttpClient(['handler' => $stack]));

        $extension->load([
            'contentful' => [
                'delivery' => [
                    'main' => [
                        'default' => \true,
                        'token' => 'b4c0n73n7fu1',
                        'space' => 'cfexampleapi',
                        'options' => [
                            'client' => 'app.http_client',
                            'logger' => \null,
                            'cache' => [
                                'pool' => \null,
                            ],
                        ],
                    ],
                ],
            ],
        ], $container);

        /** @var Client $client */
        $client = $container->get(Client::class);
        $this->assertInstanceOf(Client::class, $client);

        $this->assertFalse($hasCalledHandler);
        $space = $client->getSpace();
        $this->assertSame('Contentful Example API', $space->getName());
        $this->assertTrue($hasCalledHandler);
    }
}
