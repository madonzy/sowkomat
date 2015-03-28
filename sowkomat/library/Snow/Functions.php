<?php

/**
 * Removes duplicate values from multidimensional array.
 * 
 * @param array $array Input array.
 * @return array
 */
function array_unique_multi(array $array) {
    $result = array_map('unserialize', array_unique(array_map('serialize', $array)));

    foreach ($result as $key => $value) {
        if (is_array($value)) {
            $result[$key] = array_unique_multi($value);
        }
    }

    return $result;
}

/**
 * Removes empty values from an array.
 *
 * @param array $array Input array.
 * @param boolean $unset Unset or replace with NULL
 * @param boolean $numerics Allow numerics?
 * @return array
 */
function array_clear(array $array, $unset = false, $numerics = false) {
    foreach ($array as &$value) {
        if ((empty($numerics) || !is_numeric($value)) && empty($value)) {
            if (empty($unset)) {
                $value = null;
            } else {
                unset($value);
            }
        }
    }
    
    return $array;
}

function array_filter_keys(array $array, array $keys) {
    foreach ($array as $key => $value) {
        if (!in_array($key, $keys)) {
            unset($array[$key]);
        }
    }
    
    return $array;
}

/**
 * Remaps rowset with given array of pointing indexes.
 * 
 * @param array $array
 * @param array $map
 * @param boolean $strict
 * @return array
 */
function array_remap(array $array, array $map, $strict = false) {
    $output = array();
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $output[] = array_remap($value, $map, $strict);
        } else {
            if (!empty($map[$key])) {
                $output[$map[$key]] = $value;
            } elseif (empty($strict) || in_array($key, $map) && is_int(array_search($key, $map))) {
                $output[$key] = $value;
            }
        }
    }
    
    return $output;
}

function array_combine_columns(array $array, $index, $value = null) {
    $output = array();
    foreach ($array as $row) {
        $output[$row[$index]] = empty($value) ? $row : $row[$value];
    }
    
    return $output;
}

function trim_and_strip($input) {
    $filter = new Zend_Filter_StripTags();

    if (is_array($input)) {
        foreach ($input as &$value) {
            $value = $filter->filter(trim($value));
        }
    } else {
        $input = $filter->filter(trim($input));
    }

    return $input;
}

function array_values_recursive(array $array, $key = null) {
    $values = array();
    
    array_walk_recursive($array, function($v, $k) use($key, &$values) {
        if (is_null($key) || $k == $key) {
            array_push($values, $v);
        }
    });
    
    return $values;
}

function date_difference($start, $end, $key = 'days', $absolute = false) {
    $date = date_diff(date_create($start), date_create($end), $absolute);
    
    return empty($key) ? $date : $date->$key;
}

function format_money($value) {
    return number_format((float) $value, 2, ',', ' ');
}

?>