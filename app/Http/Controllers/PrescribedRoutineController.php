<?php

namespace App\Http\Controllers;

use App\Models\PrescribedRoutine;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrescribedRoutineController extends Controller
{
    public function index(Request $request)
    {
        $email = $request->user()?->email;

        if (! $email) {
            return response()->json([]);
        }

        return response()->json(
            PrescribedRoutine::where('user_email', $email)
                ->with('steps')
                ->latest()
                ->get()
                ->map(fn (PrescribedRoutine $r) => $this->toView($r))
        );
    }

    public function all()
    {
        return response()->json(
            PrescribedRoutine::with('steps')->latest()->get()->map(fn (PrescribedRoutine $r) => $this->toView($r))
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_email' => ['required', 'email', 'max:254', 'exists:users,email'],
            'user_name' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'total_days' => ['required', 'integer', 'min:1', 'max:365'],
            'steps' => ['required', 'array', 'min:1', 'max:100'],
            'steps.*.day' => ['required', 'integer', 'min:1', 'max:365'],
            'steps.*.title' => ['required', 'string', 'max:160'],
            'steps.*.description' => ['nullable', 'string', 'max:2000'],
            'steps.*.instructions' => ['nullable', 'string', 'max:2000'],
            'steps.*.tips' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($data['steps'] as $step) {
            abort_if($step['day'] > $data['total_days'], 422, 'Routine step day cannot exceed the routine duration.');
        }

        $routine = PrescribedRoutine::create([
            'prescribed_id' => 'prescribed-'.Str::random(8),
            'user_email' => strtolower($data['user_email']),
            'user_name' => $data['user_name'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'total_days' => $data['total_days'],
            'current_day' => 1,
            'created_by' => $this->createdBy(),
        ]);

        $steps = collect($data['steps'])->sortBy('day')->values()->map(fn ($s) => array_merge($s, [
            'day' => (int) $s['day'],
        ]));

        foreach ($steps as $step) {
            $routine->steps()->create($step);
        }

        $routine->load('steps');

        return response()->json($this->toView($routine), 201);
    }

    public function update(Request $request, PrescribedRoutine $routine)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string'],
            'description' => ['nullable', 'string'],
            'total_days' => ['sometimes', 'integer', 'min:1'],
            'current_day' => ['sometimes', 'integer', 'min:1'],
            'steps' => ['sometimes', 'array', 'min:1'],
            'steps.*.day' => ['required_with:steps', 'integer', 'min:1'],
            'steps.*.title' => ['required_with:steps', 'string'],
            'steps.*.description' => ['nullable', 'string'],
            'steps.*.instructions' => ['nullable', 'string'],
            'steps.*.tips' => ['nullable', 'string'],
        ]);

        $routine->update(collect($data)->only(['name', 'description', 'total_days', 'current_day'])->all());

        if (isset($data['steps'])) {
            $routine->steps()->delete();
            foreach (collect($data['steps'])->sortBy('day')->values() as $step) {
                $routine->steps()->create($step);
            }
        }

        $routine->load('steps');

        return response()->json($this->toView($routine));
    }

    public function advance(Request $request, PrescribedRoutine $routine)
    {
        abort_unless(
            $request->user() instanceof Admin || strcasecmp($routine->user_email, $request->user()->email) === 0,
            404,
        );

        $routine->update([
            'current_day' => min($routine->current_day + 1, $routine->total_days),
        ]);

        $routine->load('steps');

        return response()->json($this->toView($routine));
    }

    public function reset(PrescribedRoutine $routine)
    {
        $routine->update(['current_day' => 1]);

        $routine->load('steps');

        return response()->json($this->toView($routine));
    }

    public function destroy(PrescribedRoutine $routine)
    {
        $routine->delete();

        return response()->json(['message' => 'Prescribed routine deleted.']);
    }

    private function createdBy(): string
    {
        $user = request()->user();

        if (! $user) {
            return 'User';
        }

        return $user instanceof Admin ? 'Admin' : 'User';
    }

    private function toView(PrescribedRoutine $r): array
    {
        return [
            'id' => $r->prescribed_id,
            'userEmail' => $r->user_email,
            'userName' => $r->user_name,
            'name' => $r->name,
            'description' => $r->description,
            'totalDays' => $r->total_days,
            'currentDay' => $r->current_day,
            'steps' => $r->steps->map(fn ($s) => [
                'day' => $s->day,
                'title' => $s->title,
                'description' => $s->description,
                'instructions' => $s->instructions,
                'tips' => $s->tips,
            ])->values(),
            'createdBy' => $r->created_by,
            'createdAt' => $r->created_at?->valueOf(),
            'updatedAt' => $r->updated_at?->valueOf(),
        ];
    }
}