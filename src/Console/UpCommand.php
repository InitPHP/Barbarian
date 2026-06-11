<?php

/**
 * UpCommand.php
 *
 * This file is part of InitPHP Barbarian.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    https://github.com/InitPHP/Barbarian/blob/main/LICENSE  MIT
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Barbarian\Console;

use InitPHP\Barbarian\MigrationInterface;
use InitPHP\Barbarian\Migrations;
use InitPHP\Console\Input;
use InitPHP\Console\InputArgument;
use InitPHP\Console\Output;

use function is_string;

/**
 * `barbarian up` — applies migrations.
 *
 * With no `--version` it applies every discovered migration in ascending
 * order; with `--version` it (re)applies a single migration.
 */
final class UpCommand extends BarbarianCommand
{
    /** @var string */
    public $command = 'up';

    public function definition(): string
    {
        return 'Runs the up() method of the migrations.';
    }

    public function help(): string
    {
        return 'Applies all migrations, or a single one when --version is given. '
            . 'A version may be the full class name or just its timestamp, e.g. '
            . '--version=Migration_20221230153013 or --version=20221230153013.';
    }

    /**
     * @return array<int, InputArgument>
     */
    public function arguments(): array
    {
        return [
            new InputArgument('version', InputArgument::STR, '', true, 'A single migration version to apply.'),
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

        $migrations = $manager->getMigrations();
        $version = $input->getArgument('version');

        if (is_string($version) && $version !== '') {
            $class = $this->resolveVersion($migrations, $version);
            if ($class === null) {
                $output->error('The requested migration version could not be found: ' . $version);
                $output->info('Usage: barbarian up --version=20221230153013');

                return;
            }
            $this->apply($manager, $class, $output, true);

            return;
        }

        if ($migrations === []) {
            $output->info('No migrations found.');

            return;
        }
        foreach ($migrations as $class) {
            $this->apply($manager, $class, $output, false);
        }
    }

    /**
     * @param array<string, class-string<MigrationInterface>> $migrations
     * @return class-string<MigrationInterface>|null
     */
    private function resolveVersion(array $migrations, string $version): ?string
    {
        return $migrations[$version] ?? $migrations['Migration_' . $version] ?? null;
    }

    /**
     * @param class-string<MigrationInterface> $class
     */
    private function apply(Migrations $manager, string $class, Output $output, bool $force): void
    {
        $migration = new $class();
        if ($manager->upMigration($migration, $force)) {
            $output->success($class . '::up() has been executed.');

            return;
        }
        $output->info('Skipped ' . $class . '::up() (already up or it returned false).');
    }
}
