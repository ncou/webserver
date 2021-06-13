<?php


declare(strict_types=1);

namespace Chiron\WebServer;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Chiron\WebServer\Exception\WebServerException;

//https://github.com/symfony/web-server-bundle/blob/3700ded76d26311f096b37f6cf9b6fbc998f8c52/WebServerConfig.php#L135
//https://github.com/symfony/panther/blob/0e722c123724ee4363e3694f5bb32545f60fb34b/src/ProcessManager/WebServerReadinessProbeTrait.php#L44

abstract class AbstractWebServer implements WebServerInterface
{
    public function run(bool $disableOutput = true, ?callable $callback = null): void
    {
        // Ensure the server adress is not already taken.
        $this->assertAdressAvailable($this->hostname, $this->port);
        // Prepare the server command line to execute.
        $process = $this->createServerProcess();

        // Quiet mode (will unset the output callback).
        if ($disableOutput) {
            $process->disableOutput();
            $callback = null;
        }
        // Execute the command line and block the console.
        $process->run($callback); // TODO : attention il peut il y avoir des exceptions qui sont levées par cette méthode, il faudrait faire un try/catch et les convertir en WebServerException !!!!

        if (! $process->isSuccessful()) {
            // TODO : afficher seulement la ligne de commande ($process->getCommandLine()) et le getErrorOutput dans le message de l'exception ??? Attention le getErrorOutput peut être vide !!!
            throw new WebServerException(
                sprintf('Could not start Server. Exit code: %d (%s). Error output: "%s".',
                    $process->getExitCode(),
                    $process->getExitCodeText(),
                    $process->getErrorOutput()
                )
            );
        }
    }

    /**
     * Since PHP 8, @ Error Suppression operator does not silent fatal errors anymore.
     * So the fsockopen is decorated with an error_reporting() function.
     *
     * @see https://php.watch/versions/8.0/fatal-error-suppression for more.
     *
     * @throws WebServerException
     */
    protected function assertAdressAvailable(string $hostname, int $port): void
    {
        $currentState = error_reporting();
        error_reporting(0);
        $resource = fsockopen($hostname, $port);
        error_reporting($currentState);

        if (is_resource($resource)) {
            fclose($resource);
            throw new WebServerException(sprintf('The port %d is already in use.', $port));
        }
    }

    /**
     * Prepare the server command line to be runned.
     */
    abstract protected function createServerProcess(): Process;

    /**
     * @param string $address server address
     *
     * @return bool if address is already in use
     */
    //https://github.com/yiisoft/yii-console/blob/master/src/Command/Serve.php#L106
    //https://github.com/symfony/web-server-bundle/blob/d1eb905afca7d4957420c4b6809e2275d3e5e85d/WebServer.php#L138
    //https://github.com/symfony/panther/blob/fe217e2f9606ff6cfe14cd4f5fc0d92da9fad118/src/ProcessManager/WebServerReadinessProbeTrait.php#L30
    /*
    private function isAddressTaken(string $address): bool
    {
        [$hostname, $port] = explode(':', $address);

        $fp = @fsockopen($hostname, (int)$port, $errno, $errstr, 1);
        if ($fp === false) {
            return false;
        }
        fclose($fp);

        return true;
    }*/

    /*
    private function createServerProcess_SAVE(): Process
    {
        $finder = new PhpExecutableFinder();
        if (false === $binary = $finder->find(false)) {
            throw new WebServerException('Unable to find the PHP binary.');
        }

        $xdebugArgs = ini_get('xdebug.profiler_enable_trigger') ? ['-dxdebug.profiler_enable_trigger=1'] : [];

        $process = new Process(array_merge([$binary], $finder->findArguments(), $xdebugArgs, ['-dvariables_order=EGPCS', '-S', $config->getAddress(), $config->getRouter()]));
        $process->setWorkingDirectory($config->getDocumentRoot());
        $process->setTimeout(null);

        if (\in_array('APP_ENV', explode(',', getenv('SYMFONY_DOTENV_VARS')))) {
            $process->setEnv(['APP_ENV' => false]);

            if (!method_exists(Process::class, 'fromShellCommandline')) {
                // Symfony 3.4 does not inherit env vars by default:
                $process->inheritEnvironmentVariables();
            }
        }

        return $process;
    }*/
}
