<?php

/**
 * This file is part of the contentful/contentful-bundle package.
 *
 * @copyright 2015-2020 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace Atolye15\Tests\ContentfulBundle\Unit\DependencyInjection;

use Atolye15\ContentfulBundle\DependencyInjection\SymfonyIntegration;
use Atolye15\Tests\ContentfulBundle\TestCase;

class SymfonyIntegrationTest extends TestCase
{
    public function testGetData()
    {
        $integration = new SymfonyIntegration();

        $this->assertSame('contentful/contentful-bundle', $integration->getIntegrationPackageName());
        $this->assertSame('contentful.symfony', $integration->getIntegrationName());
    }
}
