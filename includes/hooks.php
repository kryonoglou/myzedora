<?php

$myzedora_hooks = [
    'actions' => [],
    'filters' => []
];

function add_action(string $tag, callable $function_to_add, int $priority = 10) {
    global $myzedora_hooks;
    $myzedora_hooks['actions'][$tag][$priority][] = $function_to_add;
    ksort($myzedora_hooks['actions'][$tag]);
}

function do_action(string $tag, ...$args) {
    global $myzedora_hooks;
    if (isset($myzedora_hooks['actions'][$tag])) {
        foreach ($myzedora_hooks['actions'][$tag] as $priority => $functions) {
            foreach ($functions as $function) {
                call_user_func_array($function, $args);
            }
        }
    }
}

function has_action(string $tag): bool {
    global $myzedora_hooks;
    return isset($myzedora_hooks['actions'][$tag]);
}

function add_filter(string $tag, callable $function_to_add, int $priority = 10) {
    global $myzedora_hooks;
    $myzedora_hooks['filters'][$tag][$priority][] = $function_to_add;
    ksort($myzedora_hooks['filters'][$tag]);
}

function apply_filters(string $tag, $value, ...$args) {
    global $myzedora_hooks;
    if (isset($myzedora_hooks['filters'][$tag])) {
        foreach ($myzedora_hooks['filters'][$tag] as $priority => $functions) {
            foreach ($functions as $function) {
                $value = call_user_func_array($function, array_merge([$value], $args));
            }
        }
    }
    return $value;
}