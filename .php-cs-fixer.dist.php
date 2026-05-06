<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setIndent('    ')
    ->setLineEnding("\n")
    ->setFinder($finder)
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@PHP83Migration' => true,
        '@PHP82Migration:risky' => true,
        '@PHPUnit100Migration:risky' => true,

        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced', 'strict' => true],
        'native_constant_invocation' => ['scope' => 'namespaced', 'strict' => true],
        'no_useless_else' => true,
        'no_useless_return' => true,
        'phpdoc_align' => false,
        'phpdoc_separation' => true,
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_no_empty_return' => false,
        'no_empty_phpdoc' => true,
        'no_blank_lines_after_phpdoc' => true,
        'phpdoc_indent' => true,
        'phpdoc_scalar' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'alpha'],
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters', 'match']],
        'single_quote' => true,
        'cast_spaces' => ['space' => 'single'],
        'concat_space' => ['spacing' => 'one'],
        'logical_operators' => true,
        'modernize_types_casting' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'final_class' => false,
        'final_internal_class' => true,
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
    ]);
