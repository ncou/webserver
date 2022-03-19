<?php

declare(strict_types=1);

namespace Chiron\WebServer\Traits;

use Chiron\WebServer\Exception\WebServerException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Process\Process;

//https://github.com/hechoendrupal/drupal-console/blob/master/src/Command/ServerCommand.php

trait WebServerTrait
{
    public function runServer(Process $server): void
    {
        $callback = $this->getOutputCallback();

        // Quiet mode (will unset the output callback).
        if ($this->output->isQuiet()) {
            $server->disableOutput();
            $callback = null;
        }
        // Execute the command line and block the console.
        $server->run($callback); // TODO : attention il peut il y avoir des exceptions qui sont levées par cette méthode, il faudrait faire un try/catch et les convertir en WebServerException !!!!

        // TODO : attention si on lance deux fois le serveur roadrunner sur la même url on obtien à la fois un message d'erreur dans la console et ensuite on affiche une seconde fois le même message. Donc c'est pas terrible !!!! Eventuellement afficher uniquement le message "Could not start Server." et puis c'est tout !!!
        // https://github.com/symfony/web-server-bundle/blob/c283d46b40b1c9dee20771433a19fa7f4a9bb97a/WebServer.php#L57
        if (! $server->isSuccessful()) {
            throw new WebServerException('Server terminated unexpectedly.');
        }
    }

    // TODO : améliorer le code; exemple avec des événements :
    //https://github.com/huang-yi/swoole-watcher/blob/master/src/Watcher.php#L76
    //https://github.com/seregazhuk/reactphp-fswatch/blob/master/src/FsWatch.php#L40
    private function getOutputCallback(): callable
    {
        $output = $this->output;

        // TODO : virer le static et utiliser $this->output pour éviter la ligne de code "$output = $this->output; et xxx use($output)" !!!!
        return static function (string $type, string $buffer) use ($output) {
            if ($type === Process::ERR && $output instanceof ConsoleOutputInterface) { // TODO : faire en sorte d'éviter cette dépendance vers la classe Process dans la partie "use" de cette classe
                $output = $output->getErrorOutput();
            }
            $output->write($buffer, false, OutputInterface::OUTPUT_RAW);
        };
    }
}
