<?php

/**
 * This file is part of the contentful/contentful-bundle package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace Atolye15\ContentfulBundle\Cache\Delivery;

use Atolye15\Delivery\Cache\CacheClearer as SdkCacheClearer;
use Atolye15\Delivery\Client;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class CacheClearer implements CacheClearerInterface
{
    /**
     * @var SdkCacheClearer
     */
    private $clearer;

    /**
     * DeliveryCacheClearer constructor.
     *
     * @param Client                 $client
     * @param CacheItemPoolInterface $cacheItemPool
     */
    public function __construct(Client $client, CacheItemPoolInterface $cacheItemPool)
    {
        $this->clearer = new SdkCacheClearer($client, $client->getResourcePool(), $cacheItemPool);
    }

    /**
     * {@inheritdoc}
     */
    public function clear($cacheDir)
    {
        $this->clearer->clear();
    }
}
