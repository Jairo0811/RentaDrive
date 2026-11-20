<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SummaryTouchTargetGuardTest extends TestCase
{
    public function test_summary_toggles_keep_a_minimum_touch_target(): void
    {
        $offenders = [];

        foreach (glob(dirname(__DIR__, 2).'/resources/views/{,*/,*/*/}*.blade.php', GLOB_BRACE) ?: [] as $file) {
            preg_match_all('/<summary\s+class="([^"]*)"/', (string) file_get_contents($file), $m);
            foreach ($m[1] as $classes) {
                if (! str_contains($classes, 'min-h-')) {
                    $offenders[] = basename($file).': '.$classes;
                }
            }
        }

        $this->assertSame([], $offenders, 'Los <summary> deben declarar una altura mínima táctil (min-h-*).');
    }
}
