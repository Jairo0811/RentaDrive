<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Fiscal\Contracts\EcfProvider;
use App\Domain\Fiscal\Providers\DisabledEcfProvider;
use App\Domain\Fiscal\Services\EcfDraftType;
use App\Domain\Fiscal\Services\EcfReadinessService;
use App\Models\FiscalProfile;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

final class EcfProviderFoundationTest extends TestCase
{
    public function test_provider_is_disabled_by_default_and_rejects_transmission(): void
    {
        $provider = app(EcfProvider::class);

        $this->assertInstanceOf(DisabledEcfProvider::class, $provider);
        $this->assertFalse($provider->configured());

        $this->expectException(LogicException::class);
        $provider->transmitSignedXml('<ECF/>', '101000001');
    }

    public function test_candidate_types_never_allocate_an_encf(): void
    {
        $this->assertSame('E31', EcfDraftType::fromKind('credit'));
        $this->assertSame('E32', EcfDraftType::fromKind('consumer'));
    }

    public function test_unknown_document_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EcfDraftType::fromKind('refund');
    }

    public function test_readiness_cannot_announce_real_issuance(): void
    {
        $status = app(EcfReadinessService::class)->summary(new FiscalProfile([
            'legal_name' => 'Compañía de Prueba',
            'rnc' => '101000001',
        ]));

        $this->assertFalse($status['provider_configured']);
        $this->assertFalse($status['issuer_authorized']);
        $this->assertFalse($status['emission_enabled']);

        $profile = new FiscalProfile(['legal_name' => 'Empresa', 'rnc' => '101000001']);
        $profile->ecf_status = 'certified';
        $status = app(EcfReadinessService::class)->summary($profile);

        $this->assertTrue($status['issuer_authorized']);
        $this->assertFalse($status['emission_enabled']);
    }
}
