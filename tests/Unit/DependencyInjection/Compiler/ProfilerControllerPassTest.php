<?php

/**
 * This file is part of the contentful/contentful-bundle package.
 *
 * @copyright 2015-2020 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace Atolye15\Tests\ContentfulBundle\ContentfulBundle\Unit\Compiler;

use Atolye15\ContentfulBundle\DependencyInjection\Compiler\ProfilerControllerPass;
use Atolye15\Tests\ContentfulBundle\TestCase;
use Symfony\Component\DependencyInjection\Definition;

class ProfilerControllerPassTest extends TestCase
{
    public function testMissingProfilerAndTwig()
    {
        $container = $this->getContainer();

        $compilerPass = new ProfilerControllerPass();
        $container->addCompilerPass($compilerPass);
        $compilerPass->process($container);

        $this->assertFalse($container->hasDefinition('contentful.delivery.profiler_controller'));
    }

    public function testMissingProfiler()
    {
        $container = $this->getContainer();
        $container->setDefinition('twig', new Definition());

        $compilerPass = new ProfilerControllerPass();
        $container->addCompilerPass($compilerPass);
        $compilerPass->process($container);

        $this->assertFalse($container->hasDefinition('contentful.delivery.profiler_controller'));
    }

    public function testMissingTwig()
    {
        $container = $this->getContainer();
        $container->setDefinition('profiler', new Definition());

        $compilerPass = new ProfilerControllerPass();
        $container->addCompilerPass($compilerPass);
        $compilerPass->process($container);

        $this->assertFalse($container->hasDefinition('contentful.delivery.profiler_controller'));
    }

    public function testProfilerAndTwigPresent()
    {
        $container = $this->getContainer();
        $container->setDefinition('profiler', new Definition());
        $container->setDefinition('twig', new Definition());
        $compilerPass = new ProfilerControllerPass();

        $container->addCompilerPass($compilerPass);
        $compilerPass->process($container);

        $this->assertTrue($container->hasDefinition('contentful.delivery.profiler_controller'));
    }
}
