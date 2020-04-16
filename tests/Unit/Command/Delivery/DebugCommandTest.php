<?php

/**
 * This file is part of the contentful/contentful-bundle package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace Atolye15\Tests\ContentfulBundle\Unit\Command\Delivery;

use Atolye15\ContentfulBundle\Command\Delivery\DebugCommand;
use Atolye15\Delivery\Client;
use Atolye15\Tests\ContentfulBundle\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class DebugCommandTest extends TestCase
{
    public function testOutput()
    {
        $client = new Client('b4c0n73n7fu1', 'cfexampleapi', 'master');
        $configurations = [
            'main' => ['service' => 'default.client'],
        ];

        $command = new DebugCommand([$client], $configurations);

        $input = new ArrayInput([
            'client-name' => 'main',
        ]);
        $output = new BufferedOutput();
        // Avoid having column wrapped in tests
        \putenv('COLUMNS=200');
        $command->run($input, $output);

        $this->assertSame($this->getFixtureContent('output.txt'), $output->fetch());

        // The command will use a default value if
        // only one client is configured and no value is given
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->run($input, $output);

        $this->assertSame($this->getFixtureContent('output.txt'), $output->fetch());
    }

    /**
     * @expectedException \Symfony\Component\Console\Exception\InvalidArgumentException
     * @expectedExceptionMessage Could not find the requested client "invalid", use "contentful:delivery:info" to check the configured clients.
     */
    public function testInvalidClient()
    {
        $client = new Client('b4c0n73n7fu1', 'cfexampleapi', 'master');
        $configurations = [
            'main' => ['service' => 'default.client'],
        ];

        $command = new DebugCommand([$client], $configurations);

        $input = new ArrayInput([
            'client-name' => 'invalid',
        ]);
        $output = new BufferedOutput();
        $command->run($input, $output);
    }

    /**
     * @expectedException \Symfony\Component\Console\Exception\RuntimeException
     * @expectedExceptionMessage Requested service was found, but data could not be loaded. Try checking client credentials.
     */
    public function testDataCannotBeFetched()
    {
        $client = new Client('invalid', 'cfexampleapi', 'master');
        $configurations = [
            'main' => ['service' => 'default.client'],
        ];

        $command = new DebugCommand([$client], $configurations);

        $input = new ArrayInput([
            'client-name' => 'main',
        ]);
        $output = new BufferedOutput();
        $command->run($input, $output);
    }
}
