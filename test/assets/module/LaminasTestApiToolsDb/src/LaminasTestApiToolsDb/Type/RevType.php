<?php

declare(strict_types=1);

namespace LaminasTestApiToolsDb\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

use function is_string;
use function strrev;

/**
 * Compatible with both DBAL 3 (paired with ORM 2) and DBAL 4 (paired with ORM 3).
 *
 * The signatures below are deliberately the DBAL 4 ones: DBAL 3 declares these
 * parameters untyped, so adding `mixed` is allowed, and narrowing the return type
 * is covariant. getName() and requiresSQLCommentHint() are abstract/present in
 * DBAL 3 and removed in DBAL 4 - keeping them satisfies DBAL 3 and is harmless
 * under DBAL 4.
 */
class RevType extends Type
{
    public const NAME = 'rev';

    /**
     * @param array<string, mixed> $column
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getGuidTypeDeclarationSQL($column);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return strrev($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return strrev($value);
    }

    /**
     * Required by DBAL 3; removed from Type in DBAL 4, where it is simply unused.
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * Present in DBAL 3; removed from Type in DBAL 4, where it is simply unused.
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
