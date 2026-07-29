<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('event'), fn ($query) => $query->where('event', $request->string('event')))
            ->when($request->string('q')->isNotEmpty(), function ($query) use ($request): void {
                $term = $request->string('q')->value();
                $search = '%'.$term.'%';
                $query->where(function ($query) use ($search, $term): void {
                    $query->where('auditable_type', 'like', $search)
                        ->when(ctype_digit($term), fn ($query) => $query->orWhere('auditable_id', (int) $term))
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search));
                });
            })
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('audit.index', compact('logs'));
    }
}
