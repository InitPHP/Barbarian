<?php

/**
 * StatusCommand.php
 *
 * This file is part of InitPHP Barbarian.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    https://github.com/InitPHP/Barbarian/blob/main/LICENSE  MIT
 * @link       https://muhammetsafak.com
 */

declare(strict_types=1);

namespace InitPHP\Barbarian\Console;

use InitPHP\Barbarian\MigrationStatus;
use InitPHP\Console\Input;
use InitPHP\Console\InputArgument;
use InitPHP\Console\Output;

use function sprintf;
use function str_pad;

/**
 * `barbarian status` — shows whether each discovered migration is applied
 * (`up`), reverted (`down`) or not yet recorded (`pending`).
 *
 * Read-only: it never changes the schema.
 */
final class StatusCommand extends BarbarianCommand
{
    /** @var string */
    public $command = 'status';

    public function definition(): string
    {
        return 'Shows the status of each migration.';
    }

    public function help(): string
    {
        return 'Lists every discovered migration alongside its recorded state: '
            . 'up (applied), down (reverted) or pending (never applied).';
    }

    /**
     * @return array<int, InputArgument>
     */
    public function arguments(): array
    {
        return [
            $this->configArgument(),
        ];
    }

    public function execute(Input $input, Output $output): void
    {
        $config = $this->loadConfig($input, $output);
        if ($config === null) {
            return;
        }

        $manager = ManagerFactory::fromConfig($config);
        foreach ($manager->getErrors() as $error) {
            $output->warning($error);
        }

        $statuses = $manager->getStatuses();
        if ($statuses === []) {
            $output->info('No migrations found.');

            return;
        }

        $formats = [
            'up'      => [Output::COLOR_GREEN],
            'down'    => [Output::COLOR_YELLOW],
            'pending' => [Output::COLOR_LIGHT_GRAY],
        ];

        foreach ($statuses as $version => $status) {
            $label = $this->label($status);
            $output->writeln(sprintf('  %s %s', str_pad('[' . $label . ']', 11), $version), [], $formats[$label]);
        }
    }

    private function label(?MigrationStatus $status): string
    {
        return match ($status) {
            MigrationStatus::Up => 'up',
            MigrationStatus::Down => 'down',
            null => 'pending',
        };
    }
}
