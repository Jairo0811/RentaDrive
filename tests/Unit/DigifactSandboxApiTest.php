<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Fiscal\Providers\DigifactSandboxApi;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;
use UnexpectedValueException;

final class DigifactSandboxApiTest extends TestCase
{
    public function test_sandbox_authentication_uses_documented_endpoint_and_tenant_identity(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://testnucdo.digifact.com/api/login/get_token' => Http::response(['Token' => 'fake-jwt-token']),
        ]);

        $token = app(DigifactSandboxApi::class)->authenticate('132752155', 'USER_TEST', 'local-test-only');

        $this->assertSame('fake-jwt-token', $token);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request->url() === 'https://testnucdo.digifact.com/api/login/get_token'
            && $request['Username'] === 'DO.132752155.USER_TEST'
            && $request['Password'] === 'local-test-only');
    }

    public function test_lookup_returns_only_sanitized_metadata_and_checks_issuer(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://testnucdo.digifact.com/api/SHAREDINFO*' => Http::response([
                'REQUEST_DATA' => [['Respuesta' => 1]],
                'RESPONSE' => [[
                    'RNCEmisor' => '132752155',
                    'NumeroSecuencia' => 'E310008569878',
                    'ResultadoEstado' => 'Aceptado',
                    'ResultadoTrackID' => 'track-test-123',
                    'Xml' => 'private-xml-must-not-be-returned',
                ]],
            ]),
        ]);

        $result = app(DigifactSandboxApi::class)->lookupExisting(
            '132752155', 'USER_TEST', 'fake-jwt-token', 'E310008569878'
        );

        $this->assertSame([
            'issuer_rnc' => '132752155',
            'encf' => 'E310008569878',
            'provider_reported_status' => 'Aceptado',
            'track_id' => 'track-test-123',
        ], $result);

        Http::assertSent(fn ($request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://testnucdo.digifact.com/api/SHAREDINFO?')
            && $request->hasHeader('Authorization', 'fake-jwt-token')
            && str_contains($request->url(), 'TAXID=132752155'));
    }

    public function test_rejects_cross_issuer_provider_response(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://testnucdo.digifact.com/api/SHAREDINFO*' => Http::response([
                'RESPONSE' => [[
                    'RNCEmisor' => '101000001',
                    'NumeroSecuencia' => 'E310008569878',
                ]],
            ]),
        ]);

        $this->expectException(UnexpectedValueException::class);

        app(DigifactSandboxApi::class)->lookupExisting(
            '132752155', 'USER_TEST', 'fake-jwt-token', 'E310008569878'
        );
    }

    public function test_malformed_encf_never_triggers_external_request(): void
    {
        Http::preventStrayRequests();

        try {
            app(DigifactSandboxApi::class)->lookupExisting(
                '132752155', 'USER_TEST', 'fake-jwt-token', 'E31-INVENTADO'
            );
            $this->fail('Se esperaba validación de parámetros.');
        } catch (InvalidArgumentException) {
            Http::assertNothingSent();
        }
    }
}
