<?php

declare(strict_types=1);

namespace noximo;

use HardCoreFilter;
use HardCoreWrapper;
use Webmozart\PathUtil\Path;

require_once 'HardCoreWrapper.php';
require_once 'HardCoreFilter.php';

final class TickTock
{
    /**
     * @var float
     */
    private $firstTimer;
    /**
     * @var float
     */
    private $lastTimer;
    /**
     * @var Tick[]
     */
    private $tick;
    /**
     * @var string[]
     */
    private $excludeFolders;
    /**
     * @var string[]
     */
    private $excludeFoldersNormalized;

    public function __construct(array $excludeFolders)
    {
        $this->firstTimer = $this->now();
        $this->lastTimer = $this->now();

        $this->excludeFolders = $excludeFolders;
    }

    /**
     * @param string[] $excludeFolders
     */
    public static function profile(array $excludeFolders): void
    {
        $timer = new self($excludeFolders);
        register_tick_function(static function () use ($timer) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $file = $backtrace[0]['file'];
            $function = $backtrace[1]['function'] ?? null;
            $class = $backtrace[1]['class'] ?? null;

            if ($timer->shouldTime($file, $class, $function) ) {
                $timer->tick($backtrace);
            }
        });

        HardCoreFilter::register();
        HardCoreWrapper::register();
    }

    private function tick(array $backtrace): void
    {
        $latest = $backtrace[0];
        $previous = $backtrace[1] ?? [];
        $now = $this->now();

        $difference = $now - $this->firstTimer;
        $lastDifference = $now - $this->lastTimer;

        $this->tick[] = new Tick(
            $latest['file'],
            $latest['line'],
            $previous['class'] ?? '',
            $previous['function'] ?? '',
            $previous['type'] ?? '',
            count($backtrace),
            $now,
            $difference,
            $lastDifference
        );

        $this->lastTimer = $now;
    }

    private function now(): float
    {
        return microtime(true);
    }

    /**
     * @param $time
     * @return string
     */
    private function formatTime(float $time): string
    {
        return str_pad((string)round($time, 3), 5, '0');
    }

    public function __destruct()
    {
        foreach ($this->tick as $key => $tick) {
            echo $key . ' ' . $tick->format() . PHP_EOL;
        }
    }

    private function shouldTime(string $file, ?string $class, ?string $function): bool
    {
        if ($class === null && in_array($function, ['require', 'include', 'require_once', 'include_once'], true)) {
            return false;
        }
        $normalizedFile = Path::normalize($file);
        foreach ($this->getNormalizedExcludeFolders() as $folder) {
            if (strpos($normalizedFile, $folder) !== false) {
                return false;
            }
        }

        return true;
    }

    private function getNormalizedExcludeFolders(): array
    {
        if ($this->excludeFoldersNormalized === null) {
            $this->excludeFoldersNormalized = [];
            foreach ($this->excludeFolders as $folder) {
                $this->excludeFoldersNormalized[] = Path::canonicalize($folder);
            }
        }

        return $this->excludeFoldersNormalized;
    }
}


