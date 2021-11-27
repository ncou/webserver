<?php

declare(strict_types=1);

namespace Chiron\WebServer;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Chiron\WebServer\AbstractWebServer;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;

//https://github.com/laravel/octane/tree/1.x/src/Commands

// TODO : lever un warning si on a un environement de configuré à PROD (cad dans le SettingsConfig->getEnvironement()) car ce PHPWebServer ne doit pas être utilisé en production !!!
//https://github.com/symfony/web-server-bundle/blob/4.4/Command/ServerRunCommand.php#L120

/**
 * Wrapper around the PHP built-in web server.
 */
final class PhpWebServer extends AbstractWebServer
{
    protected $hostname;
    protected $port;
    protected $documentRoot;
    protected $router;

    public function __construct(string $hostname, int $port, string $documentRoot, string $router = '')
    {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->documentRoot = $documentRoot;
        $this->router = $router; // TODO : essayer de passer null pour cette valeur et vérifier si le array_filter() qui est effectué plus bas enléve bien cette valeur vide !!!!
    }

    protected function createServerProcess(): Process
    {
        // Locate the PHP Binary path.
        $finder = new PhpExecutableFinder();
        if (false === $binary = $finder->find(false)) {
            throw new WebServerException('Unable to find the PHP binary.');
        }
        // Try to enable the xdebug profiler.
        $xdebugArgs = ini_get('xdebug.profiler_enable_trigger') ? ['-dxdebug.profiler_enable_trigger=1'] : []; // TODO : vérifier si on a besoin de cette instruction !!!
        // Prepare the PHP built-in web server command line.
        $process = new Process(
            array_filter(array_merge(
            [$binary],
            $finder->findArguments(),
            $xdebugArgs,
            [
                '-dvariables_order=EGPCS',
                '-S',
                sprintf('%s:%d', $this->hostname, $this->port),
//                '-t',
//                $this->documentRoot,
                $this->router,
            ]
        )));
        // Set current php directory & disable timeout.
        $process->setWorkingDirectory($this->documentRoot); // TODO : vérifier si on a besoin de cette instruction !!!
        $process->setTimeout(null);

        return $process;
    }
}
