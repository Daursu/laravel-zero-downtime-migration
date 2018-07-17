<?php

namespace Daursu\ZeroDowntimeMigration\Tests;

use Daursu\ZeroDowntimeMigration\Connections\PtOnlineSchemaChangeConnection;
use PHPUnit\Framework\TestCase;

class PtOnlineSchemaChangeConnectionTest extends TestCase
{
    public function testItExtractsTableNameFromQuery()
    {
        $query = 'alter table `users` ADD `email` varchar(255)';
        $connection = $this->getConnectionWithMockedProcess();

        $connection->expects($this->once())
            ->method('runProcess')
            ->with($this->callback(function ($command) {
                return str_contains(implode(' ', $command), 't=users');
            }))
            ->willReturn(0);

        $connection->statement($query);
    }

    public function testItExtractsTableNameWhenQuotesAreMissing()
    {
        $query = 'ALTER TABLE users CHANGE name name VARCHAR(50) NOT NULL';
        $connection = $this->getConnectionWithMockedProcess();

        $connection->expects($this->once())
            ->method('runProcess')
            ->with($this->callback(function ($command) {
                return str_contains(implode(' ', $command), 't=users');
            }))
            ->willReturn(0);

        $connection->statement($query);
    }

    public function testItGeneratesTheAuthString()
    {
        $query = 'alter table `users` ADD `email` varchar(255)';

        $connection = $this->getConnectionWithMockedProcess([
            'host' => 'server.example.com',
            'port' => '3306',
            'database' => 'zero_downtime',
            'username' => 'username',
            'password' => 'password',
        ]);

        $connection->expects($this->once())
            ->method('runProcess')
            ->with($this->callback(function ($command) {
                $command = implode(' ', $command);
                return str_contains($command, 'h=server.example.com')
                    && str_contains($command, 'P=3306')
                    && str_contains($command, 'D=zero_downtime')
                    && str_contains($command, 'u=username')
                    && str_contains($command, 'p=password');
            }))
            ->willReturn(0);

        $connection->statement($query);
    }

    public function testItRemovesAlterTableStatementFromTheQuery()
    {
        $query = 'alter table `users` ADD `email` varchar(255)';
        $connection = $this->getConnectionWithMockedProcess();

        $connection->expects($this->once())
            ->method('runProcess')
            ->with($this->callback(function ($command) {
                $command = implode(' ', $command);
                return str_contains($command, '--alter ADD `email` varchar(255)');
            }))
            ->willReturn(0);

        $connection->statement($query);
    }

    public function testAdditionalOptionsAreLoadedIn()
    {
        $query = 'alter table `users` ADD `email` varchar(255)';
        $connection = $this->getConnectionWithMockedProcess([
            'options' => [
                '--nocheck-replication-filters',
                '--nocheck-unique-key-change',
            ]
        ]);

        $connection->expects($this->once())
            ->method('runProcess')
            ->with($this->callback(function ($command) {
                return str_contains(
                    implode(' ', $command),
                    '--nocheck-replication-filters --nocheck-unique-key-change --alter'
                );
            }))
            ->willReturn(0);

        $connection->statement($query);
    }

    private function getConnectionWithMockedProcess(array $config = [])
    {
        $mock = $this->getMockBuilder(PtOnlineSchemaChangeConnection::class)
            ->setConstructorArgs([
                function () {
                },
                'test',
                '',
                $config,
            ])
            ->setMethods(['runProcess'])
            ->getMock();

        return $mock;
    }
}
