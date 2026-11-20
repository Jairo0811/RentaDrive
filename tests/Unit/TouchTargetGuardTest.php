<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TouchTargetGuardTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function buttonComponents(): array
    {
        return [
            'primary' => ['primary-button'],
            'secondary' => ['secondary-button'],
            'danger' => ['danger-button'],
        ];
    }

    #[DataProvider('buttonComponents')]
    public function test_button_components_keep_a_44px_minimum_height_on_small_screens(string $component): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/components/'.$component.'.blade.php');

        $this->assertStringContainsString('min-h-11', $source);
    }
}
