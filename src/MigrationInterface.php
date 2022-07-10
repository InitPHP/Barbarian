<?php
/**
 * MigrationInterface.php
 *
 * This file is part of Barbarian.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    ./LICENSE  MIT
 * @version    1.0.1
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Barbarian;

interface MigrationInterface
{

    public function up(QueryInterface $query): bool;

    public function down(QueryInterface $query): bool;

    public function getName(): string;

}
