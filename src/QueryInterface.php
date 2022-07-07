<?php
/**
 * QueryInterface.php
 *
 * This file is part of Barbarian.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    ./LICENSE  MIT
 * @version    1.0
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Barbarian;

interface QueryInterface
{

    /**
     * @param string $sql
     * @param array|null $arguments
     * @return false|\PDOStatement
     * @throws \PDOException
     */
    public function query(string $sql, ?array $arguments = null);

}
