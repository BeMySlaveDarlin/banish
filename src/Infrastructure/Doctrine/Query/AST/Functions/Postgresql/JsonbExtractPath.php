<?php

namespace App\Infrastructure\Doctrine\Query\AST\Functions\Postgresql;

use Scienta\DoctrineJsonFunctions\Query\AST\Functions\Postgresql\PostgresqlJsonFunctionNode;

/**
 * "JSONB_EXTRACT_PATH" "(" StringPrimary "," StringPrimary {"," StringPrimary }* ")"
 */
class JsonbExtractPath extends PostgresqlJsonFunctionNode
{
    public const string FUNCTION_NAME = 'JSONB_EXTRACT_PATH';

    /** @var string[] */
    protected $requiredArgumentTypes = [self::STRING_PRIMARY_ARG, self::STRING_PRIMARY_ARG];

    /** @var string[] */
    protected $optionalArgumentTypes = [self::STRING_PRIMARY_ARG];

    /** @var bool */
    protected $allowOptionalArgumentRepeat = true;
}
