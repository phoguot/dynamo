<?php

declare(strict_types=1);

namespace ApplicationTest\Contract;

use PHPUnit\Framework\TestCase;

class SchemaContractTest extends TestCase
{
    public function test_schema_dung_prefix_bang_va_cot_db_camel_case(): void
    {
        $schema = file_get_contents(dirname(__DIR__, 4) . '/data/schema.sql') ?: '';
        preg_match_all('/CREATE TABLE `([^`]+)` \((.*?)\n\)/s', $schema, $matches, PREG_SET_ORDER);

        self::assertGreaterThan(0, count($matches));
        foreach ($matches as $match) {
            $table = $match[1];
            $body = $match[2];

            self::assertMatchesRegularExpression(
                '/^(usr|flt|crm|sal|rnt|dsp|mnt|bil|pfm|rpt)_[a-z0-9_]+$/',
                $table,
                "Tên bảng không đúng prefix/snake_case: {$table}"
            );

            foreach (preg_split('/\R/', $body) ?: [] as $line) {
                if (!preg_match('/^\s*`([^`]+)`\s+/', $line, $columnMatch)) {
                    continue;
                }
                $column = $columnMatch[1];
                self::assertMatchesRegularExpression(
                    '/^[a-z][A-Za-z0-9]*$/',
                    $column,
                    "Cột {$table}.{$column} không theo camelCase"
                );
            }
        }
    }

    public function test_schema_khong_dung_foreign_key_va_check(): void
    {
        $schema = file_get_contents(dirname(__DIR__, 4) . '/data/schema.sql') ?: '';
        $ddlOnly = preg_replace('/^\s*--.*$/m', '', $schema) ?? $schema;

        self::assertDoesNotMatchRegularExpression('/\bFOREIGN\s+KEY\b/i', $ddlOnly);
        self::assertDoesNotMatchRegularExpression('/\bCHECK\s*\(/i', $ddlOnly);
    }
}
