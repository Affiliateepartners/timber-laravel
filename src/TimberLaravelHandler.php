<?php

namespace Liteweb\Timber\TimberLaravel;

class TimberLaravelHandler extends \Monolog\Handler\AbstractProcessingHandler
{
    private $app;

    function __construct($level = \Monolog\Logger::DEBUG, bool $bubble = true)
    {

    }

    protected function getDefaultFormatter(): \Monolog\Formatter\FormatterInterface
    {
        return new \Liteweb\Timber\TimberMonolog\TimberFormatter();
    }

    protected function write(array $record)
    {
        \Liteweb\Timber\TimberLaravel\ProcessLog::dispatch($record['formatted'])->onQueue('timber');
    }
}