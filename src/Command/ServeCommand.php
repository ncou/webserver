<?php

declare(strict_types=1);

namespace Chiron\WebServer\Command;

use Chiron\Core\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Output\OutputInterface;
use Chiron\WebServer\PhpWebServer;
use Chiron\WebServer\WebServerInterface;
use Chiron\WebServer\Exception\WebServerException;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

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
    public const EXIT_CODE_NO_DOCUMENT_ROOT = 2;
    public const EXIT_CODE_NO_ROUTING_FILE = 3;
    public const EXIT_CODE_ADDRESS_TAKEN_BY_ANOTHER_PROCESS = 5;

    private const DEFAULT_PORT = 8080;
    private const DEFAULT_DOCROOT = 'public';

    protected static $defaultName = 'serve';

    public function configure(): void
    {
        $this
            ->setDescription('Runs PHP built-in web server')
            ->setHelp('In order to access server from remote machines use 0.0.0.0:8000. That is especially useful when running server in a virtual machine.')
            ->addArgument('address', InputArgument::OPTIONAL, 'Host to serve at', 'localhost')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Port to serve at', self::DEFAULT_PORT)
            ->addOption('docroot', 't', InputOption::VALUE_OPTIONAL, 'Document root to serve from', self::DEFAULT_DOCROOT)
            ->addOption('router', 'r', InputOption::VALUE_OPTIONAL, 'Path to router script');
    }

    public function perform()
    {
        // TODO : améliorer le code !!!!
        $input = $this->input;
        $output = $this->output;

        $address = $input->getArgument('address');

        $port = $input->getOption('port');
        $docroot = $input->getOption('docroot');
        $router = $input->getOption('router');

        $documentRoot = getcwd() . '/' . $docroot; // TODO: can we do it better?

        if (strpos($address, ':') === false) {
            $address .= ':' . $port;
        }

        // TODO : remonter ce test dans la classe PhpWebServer dans le constructor ??? il faudrait aussi vérifier qu'il existe bien un fichier index.php / index.htm ou index.html dans ce répertoire !!!
        if (! is_dir($documentRoot)) {
            $this->error("Document root \"$documentRoot\" does not exist.");

            return self::FAILURE;
        }
        // TODO : remonter ce test dans la classe SapiWebServer dans le constructor ???
        if ($router !== null && ! file_exists($router)) {
            $this->error("Routing file \"$router\" does not exist.");

            return self::FAILURE;
        }


        //$output->writeln(sprintf('ThinkPHP Development server is started On <http://%s:%s/>', '0.0.0.0' == $host ? '127.0.0.1' : $host, $port));
        //$output->writeln(sprintf('You can exit with <info>`CTRL-C`</info>'));
        //$output->writeln(sprintf('Document root is: %s', $root));



        $this->success("Server listening on http://{$address}");
        /*
        $output->writeLn("Document root is \"{$documentRoot}\""); // TODO : faire un realpath sur le $documentRoot
        if ($router) {
            $output->writeLn("Routing file is \"$router\"");
        }*/
        $this->info('Quit the server with CTRL-C or COMMAND-C.');

        [$hostname, $port] = explode(':', $address);

        try {
            $server = new PhpWebServer($hostname, (int) $port, $documentRoot, $router ?? '');
            $server->run($this->isDisabledOutput(), $this->getOutputCallback());
            // TODO : il faudrait plutot faire un try/catch sur le type de classe Throwable, car la classe Process qui est utilisée dans le PhpWebServer peut lever des RuntimeException ou LogicException dans certains cas si les paramétres ne sont pas cohérents !!!
        } catch (WebServerException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function isDisabledOutput(): bool
    {
        return $this->output->isQuiet();
    }

    // TODO : améliorer le code; exemple avec des événements :
    //https://github.com/huang-yi/swoole-watcher/blob/master/src/Watcher.php#L76
    //https://github.com/seregazhuk/reactphp-fswatch/blob/master/src/FsWatch.php#L40
    private function getOutputCallback(): callable
    {
        $output = $this->output;

        // TODO : virer le static et utiliser $this->output pour éviter la ligne de code "$output = $this->output; et xxx use($output)" !!!!
        return static function (string $type, string $buffer) use ($output) {
            if (Process::ERR === $type && $output instanceof ConsoleOutputInterface) { // TODO : faire en sorte d'éviter cette dépendance vers la classe Process dans la partie "use" de cette classe
                $output = $output->getErrorOutput();
            }
            $output->write($buffer, false, OutputInterface::OUTPUT_RAW);
        };
    }
}
