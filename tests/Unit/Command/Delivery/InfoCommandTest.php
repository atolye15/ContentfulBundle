<?php

/**
 * This file is part of the contentful/contentful-bundle package.
 *
 * @copyright 2015-2020 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace Atolye15\Tests\ContentfulBundle\Unit\Command\Delivery;

use Atolye15\ContentfulBundle\Command\Delivery\InfoCommand;
use Atolye15\Tests\ContentfulBundle\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class InfoCommandTest extends TestCase
{
    public function testOutput()
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $command = new InfoCommand([]);
        $this->assertSame('contentful:delivery:info', $command->getDefaultName());
        $this->assertSame('Shows information about the configured Contentful delivery clients', $command->getDescription());

        $command->run($input, $output);
        $this->assertStringContainsStringIgnoringCase('There are no Contentful clients currently configured', $output->fetch());

        $command = new InfoCommand($this->getInfo());
        $command->run($input, $output);

        $this->assertSame($this->getFixtureContent('output.txt'), $output->fetch());
    }

    private function getInfo(): array
    {
        return [
            'default' => [
                'service' => 'contentful.delivery.default_client',
                'api' => 'DELIVERY',
                'space' => 'cfexampleapi',
                'environment' => 'master',
                'cache' => 'app.cache',
            ],
            'preview' => [
                'service' => 'contentful.delivery.default_client',
                'api' => 'DELIVERY',
                'space' => 'cfexampleapi',
                'environment' => 'master',
                'cache' => 'Not enabled',
            ],
        ];
    }
}
