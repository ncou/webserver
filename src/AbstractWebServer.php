<?php


declare(strict_types=1);

namespace Chiron\WebServer;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Chiron\WebServer\Exception\WebServerException;

//https://github.com/symfony/web-server-bundle/blob/3700ded76d26311f096b37f6cf9b6fbc998f8c52/WebServerConfig.php#L135

abstract class AbstractWebServer implements WebServerInterface
{
    private $hostname;
    private $port;

    /**
     * @var Process
     */
    private $process;

    /**
     * @throws \RuntimeException
     */
    public function __construct(string $documentRoot, string $hostname, int $port, string $router = '', array $env = null)
    {
        $this->hostname = $hostname;
        $this->port = $port;

        $finder = new PhpExecutableFinder();
        if (false === $binary = $finder->find(false)) {
            throw new WebServerException('Unable to find the PHP binary.'); // TODO : créer une ServerException ou ServerBinaryNotFoundException ou UnexpectedServerException
        }

        if (isset($_SERVER['PANTHER_APP_ENV'])) {
            if (null === $env) {
                $env = [];
            }
            $env['APP_ENV'] = $_SERVER['PANTHER_APP_ENV'];
        }

        $this->process = new Process(
            array_filter(array_merge(
                [$binary],
                $finder->findArguments(),
                [
                    '-dvariables_order=EGPCS',
                    '-S',
                    sprintf('%s:%d', $this->hostname, $this->port),
                    '-t',
                    $documentRoot,
                    $router,
                ]
            )),
            $documentRoot,
            $env,
            null,
            null
        );
        //$this->process->disableOutput();

        // Symfony Process 3.4 BC: In newer versions env variables always inherit,
        // but in 4.4 inheritEnvironmentVariables is deprecated, but setOptions was removed
        /*
        if (\is_callable([$this->process, 'inheritEnvironmentVariables']) && \is_callable([$this->process, 'setOptions'])) {
            $this->process->inheritEnvironmentVariables(true);
        }*/
    }

    public function run(bool $disableOutput = true, callable $callback = null): void
    {
        $this->checkPortAvailable($this->hostname, $this->port);

        if ($disableOutput) {
            $this->process->disableOutput();
            $callback = null;
        }

        $this->process->run($callback);

        if (! $this->process->isSuccessful()) {
            $error = 'Server terminated unexpectedly.';
            if ($this->process->isOutputDisabled()) {
                $error .= ' Run the command again with -v option for more details.';
            }

            //throw new WebServerException($error);
            throw new WebServerException(
                sprintf('Could not start %s. Exit code: %d (%s). Error output: %s',
                    'TOTO_SERVER',
                    $this->process->getExitCode(),
                    $this->process->getExitCodeText(),
                    $this->process->getErrorOutput()
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
     * @throws \RuntimeException
     */
    // TODO : renommer la méthode isAdressAvailable() et la déplacer (+passer en static) dans la classe Uri ???
    private function checkPortAvailable(string $hostname, int $port, bool $throw = true): void
    {
        $currentState = error_reporting();
        error_reporting(0);
        $resource = fsockopen($hostname, $port);
        error_reporting($currentState);
        if (\is_resource($resource)) {
            fclose($resource);
            if ($throw) {
                throw new WebServerException(sprintf('The port %d is already in use.', $port));
            }
        }
    }

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