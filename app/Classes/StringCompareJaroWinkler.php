<?php

namespace App\Classes;

class StringCompareJaroWinkler
{
    public function compare(string $str1, string $str2): float
    {
        return $this->jaroWinkler($str1, $str2, 0.1);
    }

    public  function jaroWinkler(string $string1, string $string2, float $boostThreshold, int $prefixLength = 4, float $prefixScale = 0.1): float
    {
        $jaroDistance = $this->jaro($string1, $string2);

        if ($jaroDistance < $boostThreshold) {
            return $jaroDistance;
        }

        $commonPrefixLength = $this->getCommonPrefixLength($string1, $string2, $prefixLength);

        return $jaroDistance + $commonPrefixLength * $prefixScale * (1.0 - $jaroDistance);
    }

    private function jaro(string $string1, string $string2): float
    {
        $str1_len = mb_strlen($string1);
        $str2_len = mb_strlen($string2);
        $distance = (int)floor(min($str1_len, $str2_len) / 2.0);

        $commons1 = $this->getCommonCharacters($string1, $string2, $distance);
        $commons2 = $this->getCommonCharacters($string2, $string1, $distance);

        $commons1_len = mb_strlen($commons1);
        $commons2_len = mb_strlen($commons2);

        if ($commons1_len === 0 || $commons2_len === 0) {
            return 0;
        }

        $transpositions = $this->countTranspositions($commons1, $commons2);
        return ($commons1_len / $str1_len + $commons2_len / $str2_len + ($commons1_len - $transpositions) / $commons1_len) / 3.0;
    }

    private function getCommonCharacters(string $string1, string $string2, int $allowedDistance): string
    {
        $str1_len = mb_strlen($string1);
        $str2_len = mb_strlen($string2);
        $temp_string2 = $string2;
        $commonCharacters = '';

        for ($i = 0; $i < $str1_len; $i++) {
            $char1 = $string1[$i];
            $start = max(0, $i - $allowedDistance);
            $end = min($i + $allowedDistance + 1, $str2_len);

            for ($j = $start; $j < $end; $j++) {
                if ($temp_string2[$j] === $char1) {
                    $commonCharacters .= $char1;
                    $temp_string2[$j] = ' ';
                    break;
                }
            }
        }

        return $commonCharacters;
    }

    private function countTranspositions(string $commons1, string $commons2): int
    {
        $transpositions = 0;
        $upperBound = min(mb_strlen($commons1), mb_strlen($commons2));

        for ($i = 0; $i < $upperBound; $i++) {
            if ($commons1[$i] !== $commons2[$i]) {
                $transpositions++;
            }
        }

        return (int)($transpositions / 2.0);
    }

    private function getCommonPrefixLength(string $string1, string $string2, int $minPrefixLength = 4): int
    {
        $n = min([$minPrefixLength, mb_strlen($string1), mb_strlen($string2)]);
        for ($i = 0; $i < $n; $i++) {
            if ($string1[$i] !== $string2[$i]) {
                return $i;
            }
        }

        return $n;
    }
}