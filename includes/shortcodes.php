<?php

$myzedora_shortcodes = [];

function add_shortcode(string $tag, callable $callback) {
    global $myzedora_shortcodes;
    if (!is_callable($callback)) {
        return;
    }
    $myzedora_shortcodes[$tag] = $callback;
}

function do_shortcode(string $content) {
    global $myzedora_shortcodes;
    if (empty($myzedora_shortcodes) || strpos($content, '[') === false) {
        return $content;
    }

    $pattern = '/\[(\w+)(?:\s+([^\]]*))?\](?:(.*?)\[\/\1\])?|\[(\w+)(?:\s+([^\]]*))?\]/s';

    return preg_replace_callback($pattern, 'do_shortcode_tag', $content);
}

function do_shortcode_tag($matches) {
    global $myzedora_shortcodes;

    if (isset($matches[4])) {
        $tag = $matches[4];
        $attr_string = $matches[5] ?? '';
        $inner_content = null;
    } else {
        $tag = $matches[1];
        $attr_string = $matches[2] ?? '';
        $inner_content = $matches[3] ?? null;
    }

    if (!isset($myzedora_shortcodes[$tag])) {
        return $matches[0];
    }

    $attrs = [];
    if ($attr_string) {
        preg_match_all('/(\w+)\s*=\s*"([^"]*)"/', $attr_string, $attr_matches, PREG_SET_ORDER);
        foreach ($attr_matches as $match) {
            $attrs[$match[1]] = $match[2];
        }
    }

    return call_user_func($myzedora_shortcodes[$tag], $attrs, $inner_content, $tag);
}

function run_shortcodes_on_content($content) {
    return do_shortcode($content);
}

add_filter('the_content', 'run_shortcodes_on_content');