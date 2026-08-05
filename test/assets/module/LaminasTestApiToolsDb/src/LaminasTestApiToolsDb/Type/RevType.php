<?php

declare(strict_types=1);

namespace LaminasTestApiToolsDb\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

use function is_string;
use function strrev;

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
}
