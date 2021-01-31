<?php

declare(strict_types=1);

namespace Chiron\WebServer\Bootloader;

use Chiron\Console\Console;
use Chiron\Core\Container\Bootloader\AbstractBootloader;
use Chiron\WebServer\Command\ServeCommand;

final class ServeCommandBootloader extends AbstractBootloader
{
    public function boot(Console $console): void
    {
        $console->addCommand(ServeCommand::getDefaultName(), ServeCommand::class);
    }
}
