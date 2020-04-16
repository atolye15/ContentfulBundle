<?php

/**
 * This file is part of the contentful/contentful-bundle package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace Atolye15\Tests\ContentfulBundle\Unit\Cache\Delivery;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Atolye15\ContentfulBundle\Cache\Delivery\CacheWarmer;
use Atolye15\Delivery\Client;
use Atolye15\Tests\ContentfulBundle\TestCase;

class CacheWarmerTest extends TestCase
{
    public function testWarmer()
    {
        $items = [];
        $client = new Client('b4c0n73n7fu1', 'cfexampleapi', 'master');
        $warmer = new CacheWarmer($client, new ArrayCachePool(\null, $items), \false, \false);
        $this->assertFalse($warmer->isOptional());

        $warmer->warmUp('');

        $this->assertArrayHasKey('contentful.DELIVERY.cfexampleapi.master.Space.cfexampleapi.__ALL__', $items);
        $this->assertJson($items['contentful.DELIVERY.cfexampleapi.master.Space.cfexampleapi.__ALL__'][0]);
        $this->assertArrayHasKey('contentful.DELIVERY.cfexampleapi.master.Environment.master.__ALL__', $items);
        $this->assertJson($items['contentful.DELIVERY.cfexampleapi.master.Environment.master.__ALL__'][0]);
        $this->assertArrayHasKey('contentful.DELIVERY.cfexampleapi.master.ContentType.dog.__ALL__', $items);
        $this->assertJson($items['contentful.DELIVERY.cfexampleapi.master.ContentType.dog.__ALL__'][0]);
        $this->assertArrayHasKey('contentful.DELIVERY.cfexampleapi.master.ContentType.human.__ALL__', $items);
        $this->assertJson($items['contentful.DELIVERY.cfexampleapi.master.ContentType.human.__ALL__'][0]);
        $this->assertArrayHasKey('contentful.DELIVERY.cfexampleapi.master.ContentType.cat.__ALL__', $items);
        $this->assertJson($items['contentful.DELIVERY.cfexampleapi.master.ContentType.cat.__ALL__'][0]);
    }
}
