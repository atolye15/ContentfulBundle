<?php

/**
 * This file is part of the contentful/contentful-bundle package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace Atolye15\Tests\ContentfulBundle\Unit;

use Atolye15\ContentfulBundle\ContentfulBundle;
use Atolye15\ContentfulBundle\DependencyInjection\Compiler\ProfilerControllerPass;
use Atolye15\Tests\ContentfulBundle\TestCase;

class ContentfulBundleTest extends TestCase
{
    public function testCompilerPassIsAdded()
    {
        $bundle = new ContentfulBundle();
        $container = $this->getContainer();

        $bundle->build($container);

        foreach ($container->getCompilerPassConfig()->getPasses() as $pass) {
            if ($pass instanceof ProfilerControllerPass) {
                $this->markTestAsPassed('Profiler compiler pass was successfully added');

                return;
            }
        }

        $this->fail('Profiler compiler pass was not successfully added');
    }
}
