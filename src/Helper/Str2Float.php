<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Helper;

class Str2Float
{
    public function autoParse(mixed $numberStr): ?float
    {
        if (!is_string($numberStr)) {
            return null;
        }
        $numberStr = trim($numberStr);
        if ($numberStr === '') {
            return null;
        }

        /*
         * 1) Strict "comma as decimal only", no thousands. E.g. "123,456" => 123.456
         *    Regex:  ^\d+,\d+$
         */
        if (preg_match('/^\d+,\d+$/', $numberStr)) {
            $clean = str_replace(',', '.', $numberStr);
            return (float)$clean;
        }

        /*
         * 2) Strict "dot as decimal only", no thousands. E.g. "123.456" => 123.456
         *    Regex:  ^\d+\.\d+$
         */
        if (preg_match('/^\d+\.\d+$/', $numberStr)) {
            // Already standard decimal
            return (float)$numberStr;
        }

        /*
         * 3) European thousands + decimal: E.g. "1.234.567,89"
         *    Regex:  ^\d{1,3}(\.\d{3})*,\d+$
         *    - Remove all dots (thousands)
         *    - Replace comma with dot (decimal)
         */
        if (preg_match('/^\d{1,3}(\.\d{3})*,\d+$/', $numberStr)) {
            $clean = str_replace('.', '', $numberStr);   // remove all thousands dots
            $clean = str_replace(',', '.', $clean);      // comma -> dot
            return (float)$clean;
        }

        /*
         * 4) US thousands + decimal: E.g. "1,234,567.89"
         *    Regex:  ^\d{1,3}(,\d{3})*\.\d+$
         *    - Remove commas (thousands)
         */
        if (preg_match('/^\d{1,3}(,\d{3})*\.\d+$/', $numberStr)) {
            $clean = str_replace(',', '', $numberStr);
            return (float)$clean;
        }

        /*
         * 5) Integer with only thousands (European style). E.g. "1.234.567" => 1234567
         *    Regex:  ^\d{1,3}(\.\d{3})*$
         *    - Remove dots
         */
        if (preg_match('/^\d{1,3}(\.\d{3})*$/', $numberStr)) {
            $clean = str_replace('.', '', $numberStr);
            return (float)$clean;
        }

        /*
         * 6) Integer with only thousands (US style). E.g. "1,234,567" => 1234567
         *    Regex:  ^\d{1,3}(,\d{3})*$
         *    - Remove commas
         */
        if (preg_match('/^\d{1,3}(,\d{3})*$/', $numberStr)) {
            $clean = str_replace(',', '', $numberStr);
            return (float)$clean;
        }

        /*
         * If none of the above patterns match,
         * treat this as invalid => null
         */
        return null;
    }
}
