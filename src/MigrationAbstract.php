<?php
/**
 * MigrationAbstract.php
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

abstract class MigrationAbstract implements MigrationInterface
{

    abstract public function up(QueryInterface $query): bool;

    abstract public function down(QueryInterface $query): bool;

    public final function getName(): string
    {
        $class = \get_called_class();
        if(strpos($class, "\\") === FALSE){
            return $class;
        }
        $split = explode("\\", $class);
        $name = end($split);
        return empty($name) ? $class : $name;
    }

}