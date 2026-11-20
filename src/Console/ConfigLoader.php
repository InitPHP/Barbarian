<?php

/**
 * ConfigLoader.php
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

use InitPHP\Barbarian\MigrationException;

use function array_key_exists;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;

/**
 * Reads and validates a `barbarian.json` file into a {@see Config}.
 *
 * Both the historical `table_name` key and the library-native `migrationTable`
 * key are accepted for the version table name.
 */
final class ConfigLoader
{
    /**
     * Keys that must be present in the configuration file.
     *
     * @var array<int, string>
     */
    private const REQUIRED_KEYS = ['dsn', 'username', 'password', 'folder'];

    /**
     * Builds a {@see Config} from a JSON file.
     *
     * @param string $path Absolute or relative path to the configuration file.
     * @throws MigrationException When the file cannot be read, is not valid JSON,
     *                            or is missing a required key.
     */
    public static function fromFile(string $path): Config
    {
        $contents = @file_get_contents($path);
        if ($contents === false) {
            throw new MigrationException('Could not read the configuration file: ' . $path);
        }

        return self::fromJson($contents, $path);
    }

    /**
     * Builds a {@see Config} from a JSON string.
     *
     * @param string $json   Raw JSON document.
     * @param string $source A human-readable source label used in error messages.
     * @throws MigrationException When the JSON is invalid or a required key is missing.
     */
    public static function fromJson(string $json, string $source = 'configuration'): Config
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new MigrationException('Could not parse the configuration file: ' . $source);
        }

        foreach (self::REQUIRED_KEYS as $key) {
            if (!array_key_exists($key, $data)) {
                throw new MigrationException(
                    'The configuration file must define the "' . $key . '" key: ' . $source
                );
            }
        }

        $namespace = $data['namespace'] ?? null;

        return new Config(
            (string) $data['dsn'],
            (string) $data['username'],
            (string) $data['password'],
            (string) $data['folder'],
            (string) ($data['table_name'] ?? $data['migrationTable'] ?? 'migrations_versions'),
            is_string($namespace) && $namespace !== '' ? $namespace : null
        );
    }
}
