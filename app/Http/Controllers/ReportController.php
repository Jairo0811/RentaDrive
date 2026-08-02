<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Vehicle;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.index', $this->reportData($request));
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->dateRange($request);
        $rentals = Rental::query()
            ->with(['customer', 'vehicle.model.brand'])
            ->whereBetween('start_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('start_at')
            ->cursor();

        return response()->streamDownload(function () use ($rentals): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['Código', 'Cliente', 'Vehículo', 'Inicio', 'Retorno esperado', 'Estado', 'Total'], ',', '"', '');

            foreach ($rentals as $rental) {
                fputcsv(
                    $output,
                    [
                        $rental->code,
                        $rental->customer->full_name,
                        $rental->vehicle->display_name,
                        $rental->start_at->format('Y-m-d H:i'),
                        $rental->expected_return_at->format('Y-m-d H:i'),
                        $rental->status,
                        $rental->total,
                    ],
                    ',',
                    '"',
                    '',
                );
            }

            fclose($output);
        }, 'rentadrive-operaciones-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $data = $this->reportData($request);
        $filename = 'rentadrive-reporte-'.$data['from']->format('Ymd').'-'.$data['to']->format('Ymd').'.pdf';

        return Pdf::loadView('documents.report', $data)
            ->setPaper('letter', 'landscape')
            ->download($filename);
    }

    /**
     * @return array{
     *     from: Carbon,
     *     to: Carbon,
     *     rentals: \Illuminate\Database\Eloquent\Collection<int, Rental>,
     *     metrics: array{rental_count: int, billed: mixed, collected: mixed, outstanding: mixed, utilization: float},
     *     fleetByStatus: \Illuminate\Support\Collection<string, int>
     * }
     */
    private function reportData(Request $request): array
    {
        [$from, $to] = $this->dateRange($request);

        $rentals = Rental::query()
            ->with(['customer', 'vehicle.model.brand'])
            ->whereBetween('start_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->latest('start_at')
            ->get();

        $payments = Payment::query()
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get();

        $invoices = Invoice::query()
            ->whereBetween('issued_at', [$from->toDateString(), $to->toDateString()])
            ->get();

        return [
            'from' => $from,
            'to' => $to,
            'rentals' => $rentals,
            'metrics' => [
                'rental_count' => $rentals->count(),
                'billed' => $invoices->sum('total'),
                'collected' => $payments->sum('amount'),
                'outstanding' => Invoice::query()->where('balance', '>', 0)->sum('balance'),
                'utilization' => $this->utilizationRate(),
            ],
            'fleetByStatus' => Vehicle::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ];
    }

    /**
     * @return array{Carbon, Carbon}
     */
    private function dateRange(Request $request): array
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()));
        $to = Carbon::parse($request->input('to', now()->toDateString()));

        return [$from, $to];
    }

    private function utilizationRate(): float
    {
        $total = Vehicle::query()->where('status', '!=', 'inactive')->count();

        if ($total === 0) {
            return 0;
        }

        return round((Vehicle::query()->where('status', 'rented')->count() / $total) * 100, 1);
    }
}
