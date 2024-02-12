<?php

if (!function_exists('getValueLabelKeys')) {
    /**
     * @param array $arr
     *
     * @return array<string>
     */
    function getValueLabelKeys(array $arr): string
    {
        $keys = array_keys($arr);
        $retStr = '';
        foreach ($keys as $key) {
            $retStr .= $key . ',';
        }

        return trimRightSubString($retStr, ',');
    }
} // if ( ! function_exists('getValueLabelKeys')) {


if (!function_exists('trimRightSubString')) {
    function trimRightSubString(string $s, string $substr): string
    {
        $res = preg_match('/(.*?)(' . preg_quote($substr, "/") . ')$/si', $s, $matches);
        if (!empty($matches[1])) {
            return $matches[1];
        }

        return $s;
    }
} // if (! function_exists('trimRightSubString')) {
