<?php

/**
 * MigrationInterface.php
 *
 * This file is part of InitPHP Barbarian.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    https://github.com/InitPHP/Barbarian/blob/main/LICENSE  MIT
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Barbarian;

/**
 * The contract every migration must fulfil.
 *
 * {@see up()} applies the change and {@see down()} reverts it. Both receive a
 * {@see QueryInterface} to run statements with and must report success with a
 * boolean: returning `false` (or throwing) prevents the runner from recording
 * the state transition.
 */
interface MigrationInterface
{
    /**
     * Applies the migration.
     *
     * @param QueryInterface $query Gateway used to run the migration's statements.
     * @return bool `true` on success; `false` to signal failure and abort recording.
     */
    public function up(QueryInterface $query): bool;

    /**
     * Reverts the migration.
     *
     * @param QueryInterface $query Gateway used to run the migration's statements.
     * @return bool `true` on success; `false` to signal failure and abort recording.
     */
    public function down(QueryInterface $query): bool;

    /**
     * The unique version name under which this migration is tracked.
     *
     * @return string Typically the unqualified class name, e.g. `Migration_20221230153013`.
     */
    public function getName(): string;
}
