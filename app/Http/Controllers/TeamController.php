<?php

namespace App\Http\Controllers;

use App\Models\TeamMember;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        return response()->json(
            TeamMember::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (TeamMember $m) => $this->toView($m))
        );
    }

    public function all()
    {
        return response()->json(
            TeamMember::orderBy('sort_order')->get()->map(fn (TeamMember $m) => $this->toView($m))
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'image' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $team = TeamMember::create($data);

        return response()->json($this->toView($team), 201);
    }

    public function update(Request $request, TeamMember $team)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'role' => ['sometimes', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'image' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $team->update($data);

        return response()->json($this->toView($team));
    }

    public function destroy(TeamMember $team)
    {
        $team->delete();

        return response()->json(['message' => 'Team member deleted.']);
    }

    private function toView(TeamMember $m): array
    {
        return [
            'id' => (string) $m->id,
            'name' => $m->name,
            'role' => $m->role,
            'bio' => $m->bio,
            'image' => $m->image,
        ];
    }
}