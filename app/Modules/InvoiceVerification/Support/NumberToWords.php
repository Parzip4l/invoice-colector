<?php

namespace App\Modules\InvoiceVerification\Support;

class NumberToWords
{
    public static function terbilang(float|int|string|null $number): string
    {
        if ($number === null || $number === '' || ! is_numeric($number)) {
            return '-';
        }

        $number = (float) $number;
        $abs = abs($number);

        if ($abs < 0.0001) {
            return 'Nol Rupiah';
        }

        return trim(self::convert($abs)).' Rupiah';
    }

    protected static function convert(float $number): string
    {
        $words = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        $number = floor($number);

        if ($number < 12) {
            return ' '.$words[(int) $number];
        }

        if ($number < 20) {
            return self::convert($number - 10).' Belas';
        }

        if ($number < 100) {
            return self::convert((int) ($number / 10)).' Puluh'.self::convert($number % 10);
        }

        if ($number < 200) {
            return ' Seratus'.self::convert($number - 100);
        }

        if ($number < 1000) {
            return self::convert((int) ($number / 100)).' Ratus'.self::convert($number % 100);
        }

        if ($number < 2000) {
            return ' Seribu'.self::convert($number - 1000);
        }

        if ($number < 1000000) {
            return self::convert((int) ($number / 1000)).' Ribu'.self::convert($number % 1000);
        }

        if ($number < 1000000000) {
            return self::convert((int) ($number / 1000000)).' Juta'.self::convert($number % 1000000);
        }

        if ($number < 1000000000000) {
            return self::convert((int) ($number / 1000000000)).' Miliar'.self::convert($number % 1000000000);
        }

        return self::convert((int) ($number / 1000000000000)).' Triliun'.self::convert($number % 1000000000000);
    }
}
