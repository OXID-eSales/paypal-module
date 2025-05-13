<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Helper;

class Truncate
{
    /**
     * @param string $input
     * @param int $len
     * @return string
     */
    public function truncate(string $input, int $len = 120): string
    {
        // HTML-Entities decode
        $decoded = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Make sure that the coding is UTF-8
        if (!mb_check_encoding($decoded, 'UTF-8')) {
            $decoded = mb_convert_encoding($decoded, 'UTF-8');
        }

        // Limit string to n signs
        $result = mb_substr($decoded, 0, $len, 'UTF-8');

        return $result;
    }
}
