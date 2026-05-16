<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneratedUuid;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UuidGeneratorController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $records = GeneratedUuid::with('user')
            ->when($search, fn ($q) => $q
                ->where('uuid', 'ilike', "%{$search}%")
                ->orWhere('label', 'ilike', "%{$search}%")
            )
            ->orderBy('created_at', 'desc')
            ->paginate(30)
            ->through(fn ($r) => [
                'id'         => $r->id,
                'uuid'       => $r->uuid,
                'version'    => $r->version,
                'label'      => $r->label,
                'user'       => $r->user ? ['name' => $r->user->name] : null,
                'ip_address' => $r->ip_address,
                'created_at' => $r->created_at->format('Y-m-d H:i'),
            ]);

        $stats = [
            'total'   => GeneratedUuid::count(),
            'today'   => GeneratedUuid::whereDate('created_at', today())->count(),
            'v4count' => GeneratedUuid::where('version', 'v4')->count(),
            'v1count' => GeneratedUuid::where('version', 'v1')->count(),
        ];

        $chartData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[] = [
                'date'  => now()->subDays($i)->format('M d'),
                'count' => GeneratedUuid::whereDate('created_at', $date)->count(),
            ];
        }

        return Inertia::render('Admin/UuidGenerator', [
            'records'   => $records,
            'stats'     => $stats,
            'chartData' => $chartData,
            'filters'   => ['search' => $search],
        ]);
    }

    public function destroy(GeneratedUuid $generatedUuid)
    {
        $generatedUuid->delete();
        return back()->with('status', 'UUID deleted.');
    }
}
