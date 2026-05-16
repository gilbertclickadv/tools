<?php

namespace App\Http\Controllers;

use App\Models\GeneratedUuid;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UuidGeneratorController extends Controller
{
    public function index(Request $request)
    {
        $query = GeneratedUuid::query()->orderBy('created_at', 'desc');

        if (auth()->check()) {
            $query->where('user_id', auth()->id());
        } else {
            $query->whereNull('user_id')->where('ip_address', $request->ip());
        }

        $history = $query->paginate(10)->through(fn ($r) => [
            'id'         => $r->id,
            'uuid'       => $r->uuid,
            'version'    => $r->version,
            'label'      => $r->label,
            'created_at' => $r->created_at->diffForHumans(),
        ]);

        return Inertia::render('UuidGenerator', ['history' => $history]);
    }

    /** Generate 1–100 UUIDs and store them. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'version' => ['required', 'in:v4,v1'],
            'count'   => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $results = [];
        for ($i = 0; $i < $validated['count']; $i++) {
            $uuid = $validated['version'] === 'v1'
                ? $this->generateV1()
                : (string) Str::uuid();

            $record = GeneratedUuid::create([
                'user_id'    => auth()->id(),
                'ip_address' => $request->ip(),
                'uuid'       => $uuid,
                'version'    => $validated['version'],
            ]);

            $results[] = [
                'id'         => $record->id,
                'uuid'       => $uuid,
                'version'    => $validated['version'],
                'label'      => null,
                'created_at' => $record->created_at->diffForHumans(),
            ];
        }

        return response()->json(['uuids' => $results]);
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:generated_uuids,id'],
        ]);

        $records = GeneratedUuid::whereIn('id', $validated['ids'])->get();

        foreach ($records as $record) {
            $isOwner = $record->user_id !== null
                ? $record->user_id === auth()->id()
                : $record->ip_address === $request->ip();

            if ($isOwner || auth()->user()?->is_admin) {
                $record->delete();
            }
        }

        return back();
    }

    public function destroy(Request $request, GeneratedUuid $generatedUuid)
    {
        $isOwner = $generatedUuid->user_id !== null
            ? $generatedUuid->user_id === auth()->id()
            : $generatedUuid->ip_address === $request->ip();

        if ($isOwner || auth()->user()?->is_admin) {
            $generatedUuid->delete();
        }

        return back();
    }

    /** Simple time-based UUID v1 approximation. */
    private function generateV1(): string
    {
        $time = microtime(true);
        $timeHex = str_pad(dechex((int)($time * 10000000 + 0x01b21dd213814000)), 16, '0', STR_PAD_LEFT);
        $timeLow  = substr($timeHex, 8, 8);
        $timeMid  = substr($timeHex, 4, 4);
        $timeHigh = '1' . substr($timeHex, 1, 3);
        $clockSeq = str_pad(dechex(random_int(0, 0x3fff) | 0x8000), 4, '0', STR_PAD_LEFT);
        $node     = str_pad(dechex(random_int(0, 0xffffffffffff)), 12, '0', STR_PAD_LEFT);
        return "{$timeLow}-{$timeMid}-{$timeHigh}-{$clockSeq}-{$node}";
    }
}
