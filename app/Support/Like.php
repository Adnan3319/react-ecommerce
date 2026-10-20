<?php

namespace App\Support;

/**
 * Escaping for SQL LIKE searches. "!" is the escape character because "\" is handled
 * differently by MySQL and SQLite; use it as `LIKE ? ESCAPE '!'`.
 */
final class Like
{
    public const ESCAPE = '!';

    /**
     * Makes %, _ and the escape character itself match literally.
     */
    public static function escape(string $term): string
    {
        return str_replace(
            [self::ESCAPE, '%', '_'],
            [self::ESCAPE.self::ESCAPE, self::ESCAPE.'%', self::ESCAPE.'_'],
            $term,
        );
    }
}
