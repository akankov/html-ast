#!/usr/bin/env php
<?php

/**
 * Downloads the html5lib-tests tokenizer suites into
 * tests/fixtures/html5lib-tokenizer/. The fixtures are committed, so this
 * only needs re-running to pick up upstream changes.
 *
 * Source: https://github.com/html5lib/html5lib-tests (MIT), tokenizer/ dir.
 * xmlViolation.test is deliberately excluded — it tests an XML-coercion
 * serialization contract this package does not implement.
 *
 * Usage:
 *   php bin/fetch-html5lib-tests.php
 */

declare(strict_types=1);

const FILES = [
    'test1.test',
    'test2.test',
    'test3.test',
    'test4.test',
    'contentModelFlags.test',
    'domjs.test',
    'entities.test',
    'escapeFlag.test',
    'namedEntities.test',
    'numericEntities.test',
    'pendingSpecChanges.test',
    'unicodeChars.test',
    'unicodeCharsProblematic.test',
];

const BASE_URL = 'https://raw.githubusercontent.com/html5lib/html5lib-tests/master/tokenizer/';

$targetDir = dirname(__DIR__) . '/tests/fixtures/html5lib-tokenizer';
if (!is_dir($targetDir) && !mkdir($targetDir, 0o755, true)) {
    fwrite(STDERR, "cannot create {$targetDir}\n");
    exit(1);
}

foreach (FILES as $file) {
    $contents = file_get_contents(BASE_URL . $file);
    if ($contents === false) {
        fwrite(STDERR, "download failed: {$file}\n");
        exit(1);
    }

    // Sanity: every suite file is a JSON object with a "tests" array.
    $decoded = json_decode($contents, true);
    if (!is_array($decoded) || !isset($decoded['tests']) || !is_array($decoded['tests'])) {
        fwrite(STDERR, "unexpected format: {$file}\n");
        exit(1);
    }

    file_put_contents($targetDir . '/' . $file, $contents);
    echo str_pad($file, 32), count($decoded['tests']), " tests\n";
}

echo "done → {$targetDir}\n";
