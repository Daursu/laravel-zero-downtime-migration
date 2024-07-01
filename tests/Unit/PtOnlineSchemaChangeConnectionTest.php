<?php

namespace Unit;

use Daursu\ZeroDowntimeMigration\Connections\PtOnlineSchemaChangeConnection;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class PtOnlineSchemaChangeConnectionTest extends TestCase
{
    public function testItExtractsTableNameFromQuery()
    {
        $query = 'alter table `users` ADD `email` varchar(255)';
        $connection = $this->getConnectionWithMockedProcess();

        $connection->expects($this->once())
            ->method('runProcess')
            ->with($this->callback(function ($command) {
                return Str::contains(implode(' ', $command), 't=users');
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
                return Str::contains(implode(' ', $command), 't=users');
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
                return Str::contains($command, 'h=server.example.com')
                    && Str::contains($command, 'P=3306')
                    && Str::contains($command, 'D=zero_downtime')
                    && Str::contains($command, 'u=username')
                    && Str::contains($command, 'p=password');
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
                return Str::contains($command, '--alter ADD `email` varchar(255)');
            }))
            ->willReturn(0);

        $connection->statement($query);
    }

    public function testItConcatenatesMultipleStatements()
    {
        $queries = [
            "alter table `users` add `middle_name` varchar(255)",
            "alter table `users` drop foreign key `created_by_foreign`",
        ];
        $connection = $this->getConnectionWithMockedProcess();

        $connection->expects($this->once())
            ->method('runProcess')
            ->with($this->callback(function ($command) {
                $command = implode(' ', $command);
                return Str::contains($command, '--alter add `middle_name` varchar(255), '.
                    'drop foreign key `created_by_foreign`');
            }))
            ->willReturn(0);

        $connection->statements($queries);
    }

    public function testAdditionalOptionsAreLoadedIn()
    {
        $query = 'alter table `users` ADD `email` varchar(255)';
        $connection = $this->getConnectionWithMockedProcess([
            'params' => [
                '--nocheck-replication-filters',
                '--nocheck-unique-key-change',
            ]
        ]);

        $connection->expects($this->once())
            ->method('runProcess')
            ->with($this->callback(function ($command) {
                return Str::contains(
                    implode(' ', $command),
                    '--nocheck-replication-filters --nocheck-unique-key-change --alter'
                );
            }))
            ->willReturn(0);

        $connection->statement($query);
    }

    public function testItAppliesDryRunWhenPretending()
    {
        $query = 'alter table `users` ADD `email` varchar(255)';
        $connection = $this->getConnectionWithMockedProcess();

        $connection->method('isPretending')->willReturn(true);

        $connection->expects($this->once())
            ->method('runProcess')
            ->with($this->callback(function ($command) {
                return Str::contains(
                    implode(' ', $command),
                    '--dry-run'
                );
            }))
            ->willReturn(0);

        $connection->statement($query);
    }

    public function testItHidesSensitiveInformationWhenCommandFails()
    {
        $query = 'alter table `users` ADD `email` varchar(255)';
        $process = $this->getMockedProcess();
        $password = 'regex./\\+*?[^]$)(}special{=!><|:-#\'"characters';
        $connection = $this->getMockBuilder(PtOnlineSchemaChangeConnection::class)
            ->setConstructorArgs([
                function () {
                },
                'test',
                '',
                [
                    'username' => 'hidden',
                    'password' => $password,
                ],
            ])
            ->onlyMethods(['getProcess', 'isPretending'])
            ->getMock();

        $connection->method('getProcess')->willReturn($process);
        $process->method('stop')->willReturn(1);
        $expectedException = new RuntimeException("The command \"'pt-online-schema-change' '--execute' '--nocheck-replication-filters' '--nocheck-unique-key-change' '--recursion-method=none' '--chunk-size=2000' '--alter' 'add `city` varchar(255) null' 'h=127.0.0.1,P=3306,D=laravel,u=hidden,p=$password,t=users'\" failed.");
        $process->method('mustRun')->willThrowException($expectedException);

        try {
            $connection->statement($query);
        } catch (RuntimeException $e) {
            $this->assertStringNotContainsString('hidden', $e->getMessage());
            $this->assertStringNotContainsString($password, $e->getMessage());
            $this->assertStringContainsString('*****', $e->getMessage());
        }
    }

    private function getMockedProcess()
    {
        return $this->getMockBuilder(Process::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['stop', 'mustRun'])
            ->getMock();
    }

    private function getConnectionWithMockedProcess(array $config = [])
    {
        return $this->getMockBuilder(PtOnlineSchemaChangeConnection::class)
            ->setConstructorArgs([
                function () {
                },
                'test',
                '',
                $config,
            ])
            ->onlyMethods(['runProcess', 'isPretending'])
            ->getMock();
    }
}
