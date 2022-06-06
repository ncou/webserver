<?php

declare(strict_types=1);

namespace Chiron\WebServer\Command;

use Chiron\Core\Command\AbstractCommand;
use Chiron\WebServer\Exception\WebServerException;
use Chiron\WebServer\Traits\WebAdressAvailableTrait;
use Chiron\WebServer\Traits\WebServerTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

//https://github.com/laravel/framework/blob/b9203fca96960ef9cd8860cb4ec99d1279353a8d/src/Illuminate/Foundation/Console/ServeCommand.php

// TODO : gérer avec un fichier de routing quand il y a un point dans l'url !!!!
// https://github.com/cakephp/cakephp/blob/5.x/src/Command/ServerCommand.php#L142
// https://github.com/cakephp/app/blob/4.x/webroot/index.php#L22
// https://github.com/yiisoft/app/blob/master/public/index.php#L12
// https://dev.to/crazedvic/improving-redirects-in-php-built-in-webserver-489d
// https://github.com/drupal/drupal/blob/9.4.x/.ht.router.php

// https://github.com/laravel/laravel/blob/8.x/server.php
// https://github.com/laravel/framework/blob/536f5a830c3d1bb3b5c82ca8537b08c21009632a/src/Illuminate/Foundation/Console/ServeCommand.php#L133

//https://github.com/codeigniter4/CodeIgniter4/blob/develop/system/Commands/Server/Serve.php
//https://github.com/codeigniter4/CodeIgniter4/blob/develop/system/Commands/Server/rewrite.php

//https://github.com/narrowspark/framework/blob/master/src/Viserio/Component/WebServer/Command/ServerServeCommand.php
//https://github.com/narrowspark/framework/blob/master/src/Viserio/Component/WebServer/Resources/router.php

//https://github.com/trantrungnt/BlogCategoryArticle/blob/master/server.php


//https://github.com/cubny/php-built-in-server-manager/blob/master/server
//https://github.com/nategood/httpful/blob/master/tests/bootstrap-server.php

// ROUTER : https://github.com/JBlond/php-built-in-webserver-router-script

//https://github.com/yiisoft/yii-console/blob/master/src/Command/Serve.php#L106

//https://github.com/laravel/octane/tree/1.x/src/Commands/Concerns

//https://github.com/symfony/web-server-bundle/blob/4.4/Command/ServerRunCommand.php#L126

//https://github.com/top-think/framework/blob/6.0/src/think/console/command/RunServer.php#L66

//https://github.com/ddrv/php-slim-app/blob/ad9f4055437843761341c2ab3b74e46d93f5f889/app/Command/App/AppDevCommand.php
//https://github.com/narrowspark/framework/blob/92e2d50883bede55253616e74966abc6972de3b0/src/Viserio/Component/WebServer/Command/ServerServeCommand.php
//https://github.com/codeigniter4/CodeIgniter4/blob/develop/system/Commands/Server/Serve.php
//https://github.com/cakephp/cakephp/blob/master/src/Command/ServerCommand.php

// ROUTER : https://github.com/trantrungnt/BlogCategoryArticle/blob/8b527ae02cc6131b1af59f06a42b32eb7e69388e/server.php


// TODO : vérifier si il existe bien un fichier index.php dans le répertoire du docroot (cad dans le répertoire "public") https://github.com/symfony/web-server-bundle/blob/3700ded76d26311f096b37f6cf9b6fbc998f8c52/WebServerConfig.php#L135

//https://github.com/symfony/web-server-bundle/blob/4.4/Command/ServerRunCommand.php
//https://github.com/yiisoft/yii-console/blob/master/src/Command/Serve.php

//https://github.com/selfinvoking/laravel-rr/blob/master/app/Console/Commands/RoadRunnerCommand.php
// TODO : passer la classe en final.
// TODO : déplacer dans le package chiron/sapi !!!!

//https://github.com/symfony/web-server-bundle/blob/4.4/WebServer.php
//https://github.com/symfony/panther/blob/fe217e2f9606ff6cfe14cd4f5fc0d92da9fad118/src/ProcessManager/WebServerManager.php


// TODO : passer la classe en final ???
// TODO : créer une classe abstraite de cette commande : AbstractWebServerCommand qui pourra aussi être utilisée par les autres serveurs (roadrunner/workerman/reactphp) !!!!
class ServeCommand extends AbstractCommand
{
    use WebAdressAvailableTrait;
    use WebServerTrait;

    protected static $defaultName = 'serve';

    private const DEFAULT_PORT = 8000;
    private const DEFAULT_DOCROOT = 'public'; // TODO : utiliser un directory(@public) pour récupérer cette information !!!
    private const DEFAULT_ROUTER = __DIR__ . '/../../resources/router.php';

    public function configure(): void
    {
        // TODO : au lieu de passer un paramétre "adress" il faudrait plutot passer deux paramétre "host" et "port".
        $this
            ->setDescription('Runs PHP built-in web server')
            ->setHelp('In order to access server from remote machines use 0.0.0.0:8000. That is especially useful when running server in a virtual machine.')
            ->addArgument('address', InputArgument::OPTIONAL, 'Host to serve at', 'localhost')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Port to serve at', self::DEFAULT_PORT)
            ->addOption('docroot', 't', InputOption::VALUE_OPTIONAL, 'Document root to serve from', self::DEFAULT_DOCROOT)
            ->addOption('router', 'r', InputOption::VALUE_OPTIONAL, 'Path to router script', self::DEFAULT_ROUTER);
    }

    public function perform()
    {
        $address = $this->input->getArgument('address');
        $port = $this->input->getOption('port');
        $docroot = $this->input->getOption('docroot');
        $router = $this->input->getOption('router');

        $documentRoot = getcwd() . '/' . $docroot; // TODO: can we do it better?

        if (strpos($address, ':') === false) {
            $address .= ':' . $port;
        }

        // TODO : remonter ce test dans la classe PhpWebServer dans le constructor ??? il faudrait aussi vérifier qu'il existe bien un fichier index.php / index.htm ou index.html dans ce répertoire !!!
        if (! is_dir($documentRoot)) {
            $this->error("Document root \"$documentRoot\" does not exist."); // TODO : utiliser un sprintf()

            return self::FAILURE;
        }

        // TODO : remonter ce test dans la classe SapiWebServer dans le constructor ???
        if ($router !== null && ! file_exists($router)) {
            $this->error("Routing file \"$router\" does not exist."); // TODO : utiliser un sprintf()

            return self::FAILURE;
        }

        //$output->writeln(sprintf('ThinkPHP Development server is started On <http://%s:%s/>', '0.0.0.0' == $host ? '127.0.0.1' : $host, $port));
        //$output->writeln(sprintf('You can exit with <info>`CTRL-C`</info>'));
        //$output->writeln(sprintf('Document root is: %s', $root));

        /*
        $output->writeLn("Document root is \"{$documentRoot}\""); // TODO : faire un realpath sur le $documentRoot
        if ($router) {
            $output->writeLn("Routing file is \"$router\""); // TODO : faire un realpath sur le $documentRoot
        }*/

        [$hostname, $port] = explode(':', $address);

        try {
            $this->assertAdressAvailable($hostname, (int) $port);

            //$this->success("Server listening on http://{$address}");
            //$this->info('Quit the server with CTRL-C or COMMAND-C.');



            $server = $this->createServerProcess($hostname, (int) $port, $documentRoot, $router);

            $this->info('Server running…');
            //$this->info('Quit the server with CTRL-C or COMMAND-C.');
            $this->output->writeln([
                '',
                '  Local: <fg=white;options=bold>http://'.$hostname.':'.(int) $port.' </>',
                '',
                '  <fg=yellow>Press Ctrl+C to stop the server</>',
                '',
            ]);

            $this->runServer($server);
        } catch (WebServerException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    // TODO : vérifier si c'est normal que le $router soit null !!!, et dans ce cas je suppose que $documentRoot peut aussi $etre null donc le typehint ne sera pas bon !!!
    private function createServerProcess(string $hostname, int $port, string $documentRoot, ?string $router): Process
    {
        // Locate the PHP Binary path.
        $finder = new PhpExecutableFinder();
        $binary = $finder->find(includeArgs: false);

        if ($binary === false) {
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
                    sprintf('%s:%d', $hostname, $port),
                //                '-t',
                //                $documentRoot,
                    $router,
                ]
            ))
        );
        // Set current php directory & disable timeout.
        $process->setWorkingDirectory($documentRoot); // TODO : vérifier si on a besoin de cette instruction !!!
        $process->setTimeout(null);

        return $process;
    }
}
