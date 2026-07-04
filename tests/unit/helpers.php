<?php

declare(strict_types=1);

namespace OCA\BrStunden\Tests;

function assertSameValue(mixed $expected, mixed $actual, string $message): void {
    if ($expected === $actual) {
        return;
    }

    throw new \RuntimeException(
        $message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
    );
}

function assertThrows(callable $callback, string $message): void {
    try {
        $callback();
    } catch (\InvalidArgumentException) {
        return;
    }

    throw new \RuntimeException($message);
}
