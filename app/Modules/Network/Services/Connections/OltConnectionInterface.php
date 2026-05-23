<?php

declare(strict_types=1);

namespace App\Modules\Network\Services\Connections;

interface OltConnectionInterface
{
    /**
     * Connect to the OLT.
     *
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     * @return bool
     * @throws \Exception
     */
    public function connect(string $host, int $port, string $username, string $password): bool;

    /**
     * Execute a command or array of commands and return the raw output.
     *
     * @param string|array $commands
     * @return string
     * @throws \Exception
     */
    public function execute($commands): string;

    /**
     * Disconnect from the OLT.
     */
    public function disconnect(): void;
}
