<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * La interfaz es solo oscura: text-slate-500 sobre slate-900/950 queda por debajo de 4,5:1.
 */
final class DarkThemeContrastGuardTest extends TestCase
{
    public function test_views_do_not_use_low_contrast_slate_500_text_without_a_dark_variant(): void
    {
        $offenders = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/resources/views'));

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            preg_match_all('/class="([^"]*)"/', (string) file_get_contents($file->getPathname()), $matches);

            foreach ($matches[1] as $classes) {
                if (preg_match('/(?<![:\w-])text-slate-500\b/', $classes) && ! str_contains($classes, 'dark:text-')) {
                    $offenders[] = $file->getFilename().': '.$classes;
                }
            }
        }

        $this->assertSame([], $offenders, "Usa text-slate-400 (o añade dark:text-*) en:\n".implode("\n", $offenders));
    }
}
