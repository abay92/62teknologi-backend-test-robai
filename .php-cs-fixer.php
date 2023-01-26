<?php


$finder = PhpCsFixer\Finder::create()
    ->notPath([
        './public',
        './resources',
        './storage',
        './vendor',
    ])
    ->in(__DIR__)
;


$config = new PhpCsFixer\Config();

return $config->setRules([
        '@PSR12' => true,
        'strict_param' => false,
        'braces' => true,
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_whitespace_before_comma_in_array' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_multiline_whitespace_around_double_arrow' => true,
        'normalize_index_brace' => true,
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,
        'single_line_throw' => false,
    ])
    ->setFinder($finder)

;
