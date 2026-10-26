<?php

namespace App\Http\Controllers;

use App\Models\NewsPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    public function index()
    {
        return response()->json(
            NewsPost::latest()->get()->map(fn (NewsPost $p) => $this->toView($p))
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tag' => ['required', 'in:Company,Products,Research'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'date' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'string'],
        ]);

        $post = NewsPost::create([
            'news_id' => 'news-'.Str::random(8),
            'tag' => $data['tag'],
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'date' => $data['date'] ?? null,
            'content' => $data['content'] ?? null,
            'image' => $data['image'] ?? null,
            'created_by' => $request->user()?->email === 'admin@phytofix.ng' ? 'Admin' : 'User',
        ]);

        return response()->json($this->toView($post), 201);
    }

    public function update(Request $request, NewsPost $post)
    {
        $data = $request->validate([
            'tag' => ['sometimes', 'in:Company,Products,Research'],
            'title' => ['sometimes', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'date' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'string'],
        ]);

        $post->update($data);

        return response()->json($this->toView($post));
    }

    public function destroy(NewsPost $post)
    {
        $post->delete();

        return response()->json(['message' => 'Post deleted.']);
    }

    private function toView(NewsPost $p): array
    {
        return [
            'id' => $p->news_id,
            'tag' => $p->tag,
            'title' => $p->title,
            'excerpt' => $p->excerpt,
            'date' => $p->date,
            'content' => $p->content,
            'image' => $p->image,
            'createdBy' => $p->created_by,
            'createdAt' => $p->created_at?->valueOf(),
        ];
    }
}