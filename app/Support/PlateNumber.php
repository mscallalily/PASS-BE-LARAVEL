<?php
namespace App\Support;

final class PlateNumber
{
    public static function key(string $value): string { return strtoupper(str_replace([' ', '-'], '', trim($value))); }
}
