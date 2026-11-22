<?php

declare(strict_types=1);

namespace Test\InitPHP\Barbarian\Support;

use function bin2hex;
use function file_put_contents;
use function glob;
use function is_dir;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;

/**
 * Creates throwaway directories and migration files on disk for the tests that
 * need a real folder to scan. All created directories are removed on shutdown.
 */
final class Workspace
{
    private static bool $shutdownRegistered = false;

    /** @var array<int, string> */
    private static array $dirs = [];

    /**
     * Creates and tracks a fresh, empty temporary directory.
     */
    public static function make(): string
    {
        if (!self::$shutdownRegistered) {
            register_shutdown_function([self::class, 'cleanup']);
            self::$shutdownRegistered = true;
        }

        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'barb_ws_' . bin2hex(random_bytes(6));
        mkdir($dir, 0o777, true);
        self::$dirs[] = $dir;

        return $dir;
    }

    /**
     * A namespace token guaranteed not to collide with any other test, so the
     * migration classes loaded across the suite never clash.
     */
    public static function uniqueNamespace(): string
    {
        return 'Barbarian\\Tmp\\N' . bin2hex(random_bytes(6));
    }

    /**
     * Writes a migration class file whose up() creates, and down() drops, a
     * table named after the class.
     *
     * @param string      $dir       Target directory.
     * @param string      $className The (short) class name, e.g. `Migration_20240101000000`.
     * @param string|null $namespace Namespace for the class, or null for the global namespace.
     * @param bool        $up        Return value of the generated up() method.
     * @param bool        $down      Return value of the generated down() method.
     */
    public static function writeMigration(
        string $dir,
        string $className,
        ?string $namespace = null,
        bool $up = true,
        bool $down = true,
    ): string {
        $table = 't_' . strtolower($className);
        $namespaceLine = $namespace === null ? '' : "namespace {$namespace};\n\n";
        $upReturn = $up ? 'true' : 'false';
        $downReturn = $down ? 'true' : 'false';

        $source = "<?php\n\ndeclare(strict_types=1);\n\n{$namespaceLine}"
            . "use InitPHP\\Barbarian\\MigrationAbstract;\n"
            . "use InitPHP\\Barbarian\\QueryInterface;\n\n"
            . "class {$className} extends MigrationAbstract\n{\n"
            . "    public function up(QueryInterface \$query): bool\n    {\n"
            . "        \$query->query('CREATE TABLE IF NOT EXISTS {$table} (id INTEGER PRIMARY KEY)');\n\n"
            . "        return {$upReturn};\n    }\n\n"
            . "    public function down(QueryInterface \$query): bool\n    {\n"
            . "        \$query->query('DROP TABLE IF EXISTS {$table}');\n\n"
            . "        return {$downReturn};\n    }\n}\n";

        file_put_contents($dir . DIRECTORY_SEPARATOR . $className . '.php', $source);

        return $className;
    }

    /**
     * Writes an arbitrary file into the workspace directory.
     */
    public static function writeFile(string $dir, string $name, string $contents): string
    {
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        file_put_contents($path, $contents);

        return $path;
    }

    /**
     * Removes every tracked directory and its contents.
     */
    public static function cleanup(): void
    {
        foreach (self::$dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($dir);
        }
        self::$dirs = [];
    }
}
