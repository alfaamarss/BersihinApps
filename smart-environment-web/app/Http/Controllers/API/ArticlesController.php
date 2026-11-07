<?php

namespace App\Http\Controllers\API;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArticlesController extends BaseResourceController
{
    protected string $modelClass = Article::class;
    protected array $filterable = ['title','status'];
    protected array $sortable = ['id','published_at'];
    protected ?string $permissionPrefix = 'article';

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'slug' => ['nullable','string','max:255', Rule::unique('articles','slug')],
            'summary' => ['nullable','string'],
            'content' => ['required','string'],
            'published_at' => ['nullable','date'],
            'status' => ['nullable','in:draft,published,archived'],
        ]);

        $data['slug'] = Article::generateUniqueSlug($data['slug'] ?? $data['title']);
        $data['status'] = $data['status'] ?? 'draft';

        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $data['author_id'] = optional($request->user())->id;

        $article = Article::create($data);

        return $this->ok($article->fresh());
    }

    public function update(Request $request, string $id)
    {
        $article = Article::findOrFail($id);

        $data = $request->validate([
            'title' => ['sometimes','string','max:255'],
            'slug' => ['sometimes','nullable','string','max:255', Rule::unique('articles','slug')->ignore($article->id)],
            'summary' => ['nullable','string'],
            'content' => ['sometimes','string'],
            'published_at' => ['nullable','date'],
            'status' => ['sometimes','in:draft,published,archived'],
        ]);

        if (array_key_exists('slug', $data)) {
            $base = $data['slug'];
            if ($base === null || $base === '') {
                $base = $data['title'] ?? $article->title;
            }
            $data['slug'] = Article::generateUniqueSlug($base, $article->id);
        } elseif (array_key_exists('title', $data) && empty($article->slug)) {
            $data['slug'] = Article::generateUniqueSlug($data['title'], $article->id);
        }

        if (isset($data['status']) && $data['status'] === 'published' && empty($data['published_at']) && empty($article->published_at)) {
            $data['published_at'] = now();
        }

        $article->fill($data);
        $article->save();

        return $this->ok($article->fresh());
    }
}
