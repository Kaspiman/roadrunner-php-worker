<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Installer\Environment;

use JetBrains\PhpStorm\ExpectedValues;
use Spiral\RoadRunner\Installer\Environment\Architecture\Factory;

/**
 * @psalm-type ArchitectureType = Architecture::ARCH_*
 */
final class Architecture
{
    /**
     * @var string
     */
    public const ARCH_X86_64 = 'amd64';

    /**
     * @param array|null $variables
     * @return ArchitectureType
     */
    #[ExpectedValues(valuesFromClass: Architecture::class)]
    public static function createFromGlobals(array $variables = null): string
    {
        return (new Factory())->createFromGlobals($variables);
    }

    /**
     * @return array<ArchitectureType>
     */
    public static function all(): array
    {
        static $values;

        if ($values === null) {
            $values = Enum::values(self::class, 'ARCH_');
        }

        return $values;
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return \in_array($value, self::all(), true);
    }
}