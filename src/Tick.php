<?php

declare(strict_types=1);

namespace noximo;

final class Tick
{
    /**
     * @var string
     */
    public $file;
    /**
     * @var int
     */
    public $line;
    /**
     * @var int
     */
    public $depth;
    /**
     * @var float
     */
    public $now;
    /**
     * @var float
     */
    public $difference;
    /**
     * @var float
     */
    public $lastDifference;
    /**
     * @var string
     */
    public $class;
    /**
     * @var string
     */
    public $function;
    /**
     * @var string
     */
    public $type;
    /**
     * @var string
     */
    private $command;

    public function __construct(string $file, int $line, string $class, string $function, string $type, int $depth, float $now, float $difference, float $lastDifference)
    {
        $this->file = $file;
        $this->line = $line;
        $this->depth = $depth;
        $this->now = $now;
        $this->difference = $difference;
        $this->lastDifference = $lastDifference;
        $this->class = $class;
        $this->function = $function;
        $this->type = $type;

        $this->command = $this->getCommand($this->file, $this->line);
    }

    public function format(): string
    {
        return str_repeat('.', $this->depth) . $this->file . ':' . $this->line . ' ' . $this->class . $this->type . $this->function . ' >> ' . $this->command . ' | ' . $this->formatTime($this->difference) . ' | ' . $this->formatTime($this->lastDifference);
    }

    /**
     * @param $time
     * @return string
     */
    private function formatTime(float $time): string
    {
        return str_pad((string)round($time, 20), 5, '0');
    }

    private function getCommand(string $file, int $line)
    {
        return trim(file($file)[$line - 1]);
    }
}
