<?php

namespace App\Enums;

enum RawMaterialUnitType: string
{
    case LENGTH_BASED = 'length_based';
    case OTHER = 'other';

    /**
     * Get valid units for this unit type.
     */
    public function validUnits(): array
    {
        return match ($this) {
            self::LENGTH_BASED => ['Meters', 'Yards', 'Feet', 'Inches'],
            self::OTHER => ['Pieces', 'Rolls', 'Boxes', 'Kgs', 'Grams', 'Liters', 'Packets', 'Spools', 'Cones', 'Bundles'],
        };
    }

    /**
     * Get the default unit for this unit type.
     */
    public function defaultUnit(): string
    {
        return match ($this) {
            self::LENGTH_BASED => 'Meters',
            self::OTHER => 'Pieces',
        };
    }

    /**
     * Get conversion rate to base unit (Meters) for length units.
     */
    public static function lengthConversionFactors(): array
    {
        return [
            'Meters' => 1.0,
            'Yards' => 0.9144,
            'Feet' => 0.3048,
            'Inches' => 0.0254,
        ];
    }

    /**
     * Get conversion rate from one unit to another.
     * Returns multiplier to convert a quantity from $fromUnit to $toUnit.
     */
    public static function getConversionRate(string $fromUnit, string $toUnit): float
    {
        if ($fromUnit === $toUnit) {
            return 1.0;
        }

        $factors = self::lengthConversionFactors();

        if (isset($factors[$fromUnit]) && isset($factors[$toUnit])) {
            // Convert to Meters, then to target unit
            return $factors[$fromUnit] / $factors[$toUnit];
        }

        // Return 1.0 if not conversion-compatible (e.g. Other units)
        return 1.0;
    }

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::LENGTH_BASED => 'Length-Based',
            self::OTHER => 'Other (Count/Weight)',
        };
    }

    /**
     * Get all unit types as options for dropdowns.
     */
    public static function options(): array
    {
        return array_map(fn(self $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ], self::cases());
    }
}
