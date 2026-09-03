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

    public function process(string $rawDiff): array
    {
        $lines = explode("\n", $rawDiff);

        $changedFiles = [];
        $changedFunctions = [];
        $changedEndpoints = [];
        $currentFile = null;
        $addedLines = [];

        foreach ($lines as $line) {
            // Detect file changes
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

            if ($currentFile === null)
                continue;

            // Collect added lines
            if (str_starts_with($line, '+') && !str_starts_with($line, '+++')) {
                $addedLines[] = ltrim($line, '+');
            }
        }

        // Extract function/method names from added lines
        foreach ($addedLines as $line) {
            // PHP functions
            if (preg_match('/function\s+(\w+)\s*\(/', $line, $m)) {
                $changedFunctions[] = $m[1];
            }
            // JavaScript functions
            if (preg_match('/(?:const|let|var)\s+(\w+)\s*=\s*(?:async\s*)?\(/', $line, $m)) {
                $changedFunctions[] = $m[1];
            }
            // API routes/endpoints
            if (preg_match('/[\'\"](\/[a-zA-Z0-9\/\-_\{\}]+)[\'\"]/m', $line, $m)) {
                if (strlen($m[1]) > 2) {
                    $changedEndpoints[] = $m[1];
                }
            }
        }

        return [
            'changed_files' => array_unique($changedFiles),
            'changed_functions' => array_unique($changedFunctions),
            'changed_endpoints' => array_unique($changedEndpoints),
            'summary' => $this->buildSummary(
                array_unique($changedFiles),
                array_unique($changedFunctions),
                array_unique($changedEndpoints)
            ),
        ];
    }

    protected function shouldIgnoreFile(string $file): bool
    {
        foreach ($this->ignoredPatterns as $pattern) {
            if (str_contains($file, $pattern))
                return true;
        }
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        return in_array($ext, $this->ignoredExtensions);
    }

    protected function buildSummary(array $files, array $functions, array $endpoints): string
    {
        $summary = "Changed files:\n" . implode("\n", $files);
        if (!empty($functions)) {
            $summary .= "\n\nModified functions/methods:\n" . implode(", ", $functions);
        }
        if (!empty($endpoints)) {
            $summary .= "\n\nChanged API endpoints:\n" . implode(", ", $endpoints);
        }
        return $summary;
    }
}