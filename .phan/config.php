<?php

declare(strict_types=1);

return [
    'target_php_version' => '8.3',
    'minimum_target_php_version' => '8.3',
    'allow_missing_properties' => false,
    'null_casts_as_any_type' => false,
    'null_casts_as_array' => false,
    'array_casts_as_null' => false,
    'scalar_implicit_cast' => false,
    'scalar_array_key_cast' => false,
    'ignore_undeclared_variables_in_global_scope' => false,
    'backward_compatibility_checks' => false,
    'check_docblock_signature_return_type_match' => true,
    'prefer_narrowed_phpdoc_param_type' => true,
    'prefer_narrowed_phpdoc_return_type' => true,
    'analyzed_file_extensions' => ['php'],
    'directory_list' => [
        'src',
        'tests',
        'vendor',
    ],
    'exclude_analysis_directory_list' => [
        'vendor',
    ],
    'exclude_file_regex' => '@^vendor/rector/rector/stubs-rector/.*$@',
    'plugins' => [
        'AlwaysReturnPlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'DuplicateExpressionPlugin',
        'EmptyStatementListPlugin',
        'LoopVariableReusePlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
        'SleepCheckerPlugin',
        'UnreachableCodePlugin',
        'UnusedSuppressionPlugin',
        'UseReturnValuePlugin',
    ],
    'suppress_issue_types' => [],
];
