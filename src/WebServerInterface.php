<?php


declare(strict_types=1);

namespace Chiron\Serving;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Chiron\Serving\Exception\WebServerException;

interface WebServerInterface
{
    public function run(bool $disableOutput = true, callable $callback = null): void;
}
