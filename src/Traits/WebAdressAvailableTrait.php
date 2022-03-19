<?php

declare(strict_types=1);

namespace Chiron\WebServer\Traits;

use Chiron\WebServer\Exception\WebServerException;

trait WebAdressAvailableTrait
{
    /**
     * Since PHP 8, @ Error Suppression operator does not silent fatal errors anymore.
     * So the fsockopen is decorated with an mute error_reporting() function.
     *
     * @see https://php.watch/versions/8.0/fatal-error-suppression for more.
     *
     * @throws WebServerException
     */
    // TODO : eventuellement séparer cette méthode en deux pour avoir une méthode protected isAdressTaken(): bool qui pourrait être utilisable directement. Et conserver cette méthode pour lever l'exception si le booléen retourné par isAdressTaken() à false !!!
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
}
