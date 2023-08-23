<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Digest\Tests\integration;

use Flarum\Foundation\Application;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

trait RunsConsoleTests
{
    protected $console;

    protected function console(): ConsoleApplication
    {
        if (is_null($this->console)) {
            $this->console = new ConsoleApplication('Flarum', Application::VERSION);
            $this->console->setAutoExit(false);

            foreach ($this->app()->getConsoleCommands() as $command) {
                $this->console->add($command);
            }
        }

        return $this->console;
    }

    protected function runCommand(array $inputArray): string
    {
        $input = new ArrayInput($inputArray);
        $output = new BufferedOutput();

        $this->console()->run($input, $output);

        return trim($output->fetch());
    }
}
