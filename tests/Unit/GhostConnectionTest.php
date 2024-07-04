<?php

namespace Unit;

use Daursu\ZeroDowntimeMigration\Connections\GhostConnection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class GhostConnectionTest extends TestCase
{
    public function testItHidesSensitiveInformationWhenCommandFails()
    {
        $query = 'alter table `users` ADD `email` varchar(255)';
        $process = $this->getMockedProcess();
        $password = 'regex./\\+*?[^]$)(}special{=!><|:-#\'"characters';
        $connection = $this->getMockBuilder(GhostConnection::class)
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
        $expectedException = new RuntimeException("The command \"'gh-ost' '--max-load=Threads_running=25' '--critical-load=Threads_running=1000' '--chunk-size=1000' '--throttle-control-replicas=myreplica.1.com,myreplica.2.com' '--max-lag-millis=1500' '--verbose' '--switch-to-rbr' '--exact-rowcount' '--concurrent-rowcount' '--default-retries=120' '--user=hidden' '--password=$password' '--host=127.0.0.1' '--port=3306' '--database=laravel9' '--table=users' '--alter=add `city` varchar(255) null' '--execute'\" failed.");
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
}
