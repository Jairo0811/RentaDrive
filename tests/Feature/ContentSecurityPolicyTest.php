<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Security\ContentSecurityPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

final class ContentSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function policy(string $uri = '/login'): string
    {
        return (string) $this->get($uri)->headers->get('Content-Security-Policy');
    }

    public function test_pages_send_an_enforced_policy_with_a_per_request_nonce(): void
    {
        $first = $this->policy();
        $second = $this->policy();

        preg_match("/script-src [^;]*'nonce-([^']+)'/", $first, $a);
        preg_match("/script-src [^;]*'nonce-([^']+)'/", $second, $b);

        $this->assertNotEmpty($a[1] ?? null);
        $this->assertNotSame($a[1], $b[1], 'El nonce debe cambiar en cada petición.');
    }

    public function test_script_and_style_elements_never_allow_unsafe_inline(): void
    {
        $policy = $this->policy();

        $scriptSrc = $this->directive($policy, 'script-src');
        $styleElem = $this->directive($policy, 'style-src-elem');

        $this->assertStringNotContainsString("'unsafe-inline'", $scriptSrc);
        $this->assertStringNotContainsString("'unsafe-inline'", $styleElem);
        $this->assertStringNotContainsString('*', $scriptSrc);
        $this->assertStringContainsString("'self'", $scriptSrc);
    }

    public function test_policy_blocks_plugins_framing_and_foreign_form_targets(): void
    {
        $policy = $this->policy();

        foreach (["default-src 'self'", "object-src 'none'", "frame-ancestors 'none'", "form-action 'self'", "base-uri 'self'"] as $expected) {
            $this->assertStringContainsString($expected, $policy);
        }

        $this->assertStringContainsString('report-uri /csp-report', $policy);
    }

    public function test_only_font_awesome_cdn_is_allowed_externally(): void
    {
        $policy = $this->policy();

        preg_match_all('#https?://[^\s;]+#', $policy, $matches);

        $this->assertSame(['https://cdnjs.cloudflare.com'], array_values(array_unique($matches[0])));
    }

    public function test_inline_scripts_in_error_pages_carry_the_nonce_and_use_no_inline_handlers(): void
    {
        Vite::useCspNonce('nonce-de-prueba');

        foreach (['errors.404', 'errors.500'] as $view) {
            $html = view($view)->render();

            $this->assertStringContainsString('nonce="nonce-de-prueba"', $html);
            $this->assertStringNotContainsString('onclick=', $html);
        }
    }

    public function test_report_only_mode_uses_the_report_only_header(): void
    {
        config(['rentadrive.security.csp.mode' => 'report-only']);

        $response = $this->get('/login');

        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }

    public function test_policy_can_be_turned_off_explicitly(): void
    {
        config(['rentadrive.security.csp.mode' => 'off']);

        $response = $this->get('/login');

        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $this->assertNull($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_unknown_mode_falls_back_to_enforce(): void
    {
        config(['rentadrive.security.csp.mode' => 'banana']);

        $this->assertSame(ContentSecurityPolicy::MODE_ENFORCE, ContentSecurityPolicy::mode());
        $this->assertNotSame('', $this->policy());
    }

    public function test_extra_origins_are_validated_before_use(): void
    {
        config(['rentadrive.security.csp.img' => 'https://cdn.example.com, javascript:alert(1), https://evil.com;script-src *']);

        $img = $this->directive($this->policy(), 'img-src');

        $this->assertStringContainsString('https://cdn.example.com', $img);
        $this->assertStringNotContainsString('javascript:', $img);
        $this->assertStringNotContainsString('evil.com', $img);
    }

    public function test_violation_reports_are_logged_without_query_strings_or_bodies(): void
    {
        Log::spy();

        $this->postJson('/csp-report', ['csp-report' => [
            'effective-directive' => 'script-src-elem',
            'blocked-uri' => 'https://evil.example/x.js?token=SECRETO',
            'document-uri' => 'http://localhost/book/acme/reservation/TOKEN123?t=otro',
            'line-number' => 12,
        ]], ['Content-Type' => 'application/csp-report'])->assertNoContent();

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context): bool {
            $dump = json_encode($context);

            return $message === 'security.csp_violation'
                && $context['blocked'] === 'https://evil.example'
                && ! str_contains((string) $dump, 'SECRETO')
                && ! str_contains((string) $dump, 'TOKEN123');
        })->once();
    }

    public function test_report_endpoint_ignores_oversized_or_malformed_payloads(): void
    {
        Log::spy();

        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], str_repeat('a', 9000))->assertNoContent();
        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], 'no es json')->assertNoContent();

        Log::shouldNotHaveReceived('warning');
    }

    private function directive(string $policy, string $name): string
    {
        foreach (explode('; ', $policy) as $part) {
            if (str_starts_with($part, $name.' ')) {
                return $part;
            }
        }

        return '';
    }
}
