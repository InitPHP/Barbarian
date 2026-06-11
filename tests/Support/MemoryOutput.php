<?php

declare(strict_types=1);

namespace Test\InitPHP\Barbarian\Support;

use InitPHP\Console\Output;

use function preg_replace;
use function rewind;
use function stream_get_contents;

/**
 * Test double that captures everything written to an in-memory stream so the
 * console commands can be exercised without touching the real terminal.
 */
final class MemoryOutput extends Output
{
    /** @var resource */
    private $bufferHandle;

    public function __construct()
    {
        $buffer = fopen('php://memory', 'r+');
        $stdin = fopen('php://memory', 'r+');

        $this->bufferHandle = $buffer;
        parent::__construct($buffer, $stdin);
    }

    /**
     * Everything written so far, including SGR escape sequences.
     */
    public function captured(): string
    {
        rewind($this->bufferHandle);

        return (string) stream_get_contents($this->bufferHandle);
    }

    /**
     * The captured output with the ANSI colour codes stripped, for readable
     * assertions.
     */
    public function plain(): string
    {
        return (string) preg_replace('/\e\[[0-9;]*m/', '', $this->captured());
    }
}
