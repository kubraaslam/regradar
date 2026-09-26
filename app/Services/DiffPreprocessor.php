<?php

namespace App\Services;

class DiffPreprocessor
{
    // File extensions to ignore
    protected array $ignoredExtensions = [
        'lock',
        'json',
        'md',
        'txt',
        'png',
        'jpg',
        'gif',
        'svg',
        'ico',
        'pdf',
        'min.js',
        'min.css'
    ];

    // File patterns to ignore (auto-generated)
    protected array $ignoredPatterns = [
        'migration',
        'composer.lock',
        'package-lock',
        'yarn.lock',
        'storage/',
        'public/build'
    ];

    /**
     * Path segments that mark a file as belonging to a test suite.
     *
     * Deliberately excludes "testing": framework directories such as
     * src/Illuminate/Foundation/Testing contain shipped production code that
     * happens to provide test helpers, and misclassifying those would hide
     * genuine regressions.
     */
    protected array $testSegments = ['test', 'tests', 'spec', 'specs', '__tests__'];

    /** Filename suffixes that mark a test across PHP, Ruby, Python and JS. */
    protected array $testSuffixes = ['test.php', 'test.rb', 'test.py', 'spec.rb', 'test.js', 'spec.js', 'test.ts', 'spec.ts'];

    protected array $docSegments = ['docs', 'doc'];

    public function process(string $rawDiff): array
    {
        $lines = explode("\n", $rawDiff);

        $changedFiles = [];
        $addedLinesByFile = [];
        $currentFile = null;

        foreach ($lines as $line) {
            if (str_starts_with($line, 'diff --git')) {
                preg_match('#diff --git a/(.*?) b/#', $line, $matches);

                if (!empty($matches[1])) {
                    $file = $matches[1];

                    if (!$this->shouldIgnoreFile($file)) {
                        $currentFile = $file;
                        $changedFiles[] = $file;
                    } else {
                        $currentFile = null;
                    }
                }

                continue;
            }

            if ($currentFile === null) {
                continue;
            }

            if (str_starts_with($line, '+') && !str_starts_with($line, '+++')) {
                // Attributed to its file, so that signals extracted from test
                // files are never reported as production changes.
                $addedLinesByFile[$currentFile][] = ltrim($line, '+');
            }
        }

        $changedFiles = array_values(array_unique($changedFiles));

        $productionFiles = [];
        $testFiles = [];
        $docFiles = [];

        foreach ($changedFiles as $file) {
            match ($this->classify($file)) {
                'test' => $testFiles[] = $file,
                'doc' => $docFiles[] = $file,
                default => $productionFiles[] = $file,
            };
        }

        [$productionFunctions, $productionEndpoints] = $this->extractSignals($addedLinesByFile, $productionFiles);
        [$testFunctions, $testEndpoints] = $this->extractSignals($addedLinesByFile, $testFiles);

        return [
            // Retained for the report view and risk scoring.
            'changed_files' => $changedFiles,

            'production_files' => $productionFiles,
            'test_files' => $testFiles,
            'doc_files' => $docFiles,
            'has_production_changes' => $productionFiles !== [],

            // Production only, so downstream matching is never misled by a
            // string that merely appears in a test assertion.
            'changed_functions' => $productionFunctions,
            'changed_endpoints' => $productionEndpoints,

            'test_functions' => $testFunctions,
            'test_endpoints' => $testEndpoints,

            'summary' => $this->buildSummary(
                $productionFiles,
                $testFiles,
                $docFiles,
                $productionFunctions,
                $productionEndpoints,
                $testEndpoints
            ),
        ];
    }

    protected function classify(string $file): string
    {
        $segments = array_map('strtolower', explode('/', $file));
        $basename = strtolower(basename($file));

        foreach ($this->testSegments as $segment) {
            if (in_array($segment, $segments, true)) {
                return 'test';
            }
        }

        foreach ($this->testSuffixes as $suffix) {
            if (str_ends_with($basename, '_' . $suffix) || str_ends_with($basename, '.' . $suffix)) {
                return 'test';
            }
        }

        // PHP convention: SomethingTest.php
        if (preg_match('/test\.(php|js|ts)$/', $basename)) {
            return 'test';
        }

        foreach ($this->docSegments as $segment) {
            if (in_array($segment, $segments, true)) {
                return 'doc';
            }
        }

        return 'production';
    }

    /**
     * Pull function names and endpoint-like strings out of the added lines of
     * the given files only.
     *
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    protected function extractSignals(array $addedLinesByFile, array $files): array
    {
        $functions = [];
        $endpoints = [];

        foreach ($files as $file) {
            foreach ($addedLinesByFile[$file] ?? [] as $line) {
                // PHP and Python functions
                if (preg_match('/(?:function|def)\s+(\w+)\s*\(/', $line, $m)) {
                    $functions[] = $m[1];
                }

                // Ruby methods
                if (preg_match('/^\s*def\s+(\w+)/', $line, $m)) {
                    $functions[] = $m[1];
                }

                // JavaScript functions
                if (preg_match('/(?:const|let|var)\s+(\w+)\s*=\s*(?:async\s*)?\(/', $line, $m)) {
                    $functions[] = $m[1];
                }

                // Route-like string literals
                if (preg_match('/[\'\"](\/[a-zA-Z0-9\/\-_\{\}]+)[\'\"]/m', $line, $m)) {
                    if (strlen($m[1]) > 2) {
                        $endpoints[] = $m[1];
                    }
                }
            }
        }

        return [
            array_values(array_unique($functions)),
            array_values(array_unique($endpoints)),
        ];
    }

    protected function shouldIgnoreFile(string $file): bool
    {
        foreach ($this->ignoredPatterns as $pattern) {
            if (str_contains($file, $pattern)) {
                return true;
            }
        }

        $ext = pathinfo($file, PATHINFO_EXTENSION);

        return in_array($ext, $this->ignoredExtensions);
    }

    /**
     * Describe the diff with production and non-production changes clearly
     * separated.
     *
     * The separation matters because a change confined to test files cannot
     * alter application behaviour. Stating that explicitly, rather than leaving
     * the model to infer it from a path, gives every prompting strategy the same
     * signal, including zero-shot which has no examples to learn the pattern
     * from.
     */
    protected function buildSummary(
        array $productionFiles,
        array $testFiles,
        array $docFiles,
        array $functions,
        array $endpoints,
        array $testEndpoints
    ): string {
        $parts = [];

        if ($productionFiles === []) {
            $parts[] = "NOTE: This pull request changes no production code. "
                . "Only test or documentation files were modified, so application behaviour is unchanged.";
        }

        $parts[] = "Production files changed:\n"
            . ($productionFiles ? implode("\n", $productionFiles) : '(none)');

        if ($functions) {
            $parts[] = "Modified functions or methods in production files:\n" . implode(', ', $functions);
        }

        if ($endpoints) {
            $parts[] = "Endpoints changed in production files:\n" . implode(', ', $endpoints);
        }

        if ($testFiles) {
            $parts[] = "Test files changed:\n" . implode("\n", $testFiles);
        }

        if ($docFiles) {
            $parts[] = "Documentation files changed:\n" . implode("\n", $docFiles);
        }

        if ($testEndpoints) {
            $parts[] = "Endpoints referenced inside the changed test files "
                . "(these are assertions, not changes):\n" . implode(', ', $testEndpoints);
        }

        return implode("\n\n", $parts);
    }
}
