<?php

namespace Daursu\ZeroDowntimeMigration\Connections;

use Illuminate\Container\Container;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;

abstract class BaseConnection extends MySqlConnection
{
    /**
     * Runs the pt-online-schema-change process.
     *
     * @param array $command
     * @return int
     */
    public function runProcess(array $command): int
    {
        // Strip empty lines
        $command = array_filter($command);
        
        $this->outputCommand($command);

        $process = new Process($command);
        $process->setTimeout(null);
        $process->mustRun(function ($type, $buffer) {
            $this->output($buffer, false);
        });

        return $process->stop();
    }

    /**
     * Returns the table name.
     *
     * @param  string $query
     * @return string
     */
    protected function extractTableFromQuery(string $query): string
    {
        preg_match('/table `?(.*?)`?\s/i', $query, $matches);

        return Arr::get($matches, '1');
    }

    /**
     * Removes the table name from the query.
     *
     * @param  string $query
     * @return string
     */
    protected function cleanQuery(string $query): string
    {
        $table = $this->extractTableFromQuery($query);
        $pos = strpos($query, $table);

        return trim(substr($query, $pos + strlen($table) + 1));
    }

    /**
     * Check if the migrator was called with pretend.
     *
     * @return bool
     */
    protected function isPretending(): bool
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        return collect($trace)->contains(function ($trace) {
            return Arr::get($trace, 'class') === Migrator::class
                && Arr::get($trace, 'function') === 'pretendToRun';
        });
    }

    /**
     * Output the command running.
     *
     * @param array $command
     */
    protected function outputCommand(array $command)
    {
        $this->output(
            sprintf('[%s] Running: %s', date('Y-m-d H:i:s'), $this->maskSensitiveInformation($command))
        );
    }

    /**
     * Output to the console.
     *
     * @param string $message
     * @param bool   $newLine
     */
    protected function output(string $message, bool $newLine = true)
    {
        if (Container::getInstance()->runningInConsole()) {
            $output = new ConsoleOutput();
            $command = $newLine ? 'writeln' : 'write';
            $output->{$command}(sprintf('<comment>%s</comment>', $message));
        }
    }

    /**
     * Hide the username/pw from console output.
     *
     * @param array $command
     * @return string
     */
    protected function maskSensitiveInformation(array $command): string
    {
        return collect($command)->implode(' ');
    }
}
