<?php

namespace App\Service\Doctrine\Query\AST\Functions\Postgresql;

use Scienta\DoctrineJsonFunctions\Query\AST\Functions\Postgresql\PostgresqlJsonOperatorFunctionNode;

/**
 * "JSONB_GET_TEXT" "(" StringPrimary "," AlphaNumeric ")"
 */
class JsonbGetText extends PostgresqlJsonOperatorFunctionNode
{
    public const string FUNCTION_NAME = 'JSONB_GET_TEXT';
    public const string OPERATOR = '::JSONB->>';

    /** @var string[] */
    protected $requiredArgumentTypes = [self::STRING_PRIMARY_ARG, self::VALUE_ARG];
}
