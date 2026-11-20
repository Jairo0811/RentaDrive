<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Lookups\LookupCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LookupController extends Controller
{
    public function __invoke(Request $request, string $type, LookupCatalog $catalog): JsonResponse
    {
        abort_unless(in_array($type, LookupCatalog::TYPES, true), 404);
        abort_unless($request->user()?->canAny(LookupCatalog::permissions($type)), 403);

        $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'scope' => ['nullable', 'string', 'in:rental,reservation'],
        ]);

        return response()->json($catalog->search($type, (string) $request->query('q', ''), $request->query('scope')));
    }
}
