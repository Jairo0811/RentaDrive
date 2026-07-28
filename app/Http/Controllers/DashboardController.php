<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Security\Services\FoundationSummaryService;
use Illuminate\Contracts\View\View;

final class DashboardController extends Controller
{
    public function __invoke(FoundationSummaryService $summaryService): View
    {
        return view('dashboard', [
            'summary' => $summaryService->get(),
            'foundation' => [
                ['label' => 'Autenticación', 'status' => 'Operativa'],
                ['label' => 'Roles y permisos', 'status' => 'Operativos'],
                ['label' => 'Interfaz responsive', 'status' => 'Operativa'],
                ['label' => 'Modo oscuro', 'status' => 'Operativo'],
            ],
        ]);
    }
}
