<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $query = Article::query()->latest('published_at')->latest();

        if ($search = trim((string) $request->input('search'))) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            if (in_array($status, Article::STATUSES, true)) {
                $query->where('status', $status);
            }
        }

        $articles = $query->paginate(10)->withQueryString();
        $statusOptions = $this->statusOptions();

        return view('admin.articles.index', compact('articles', 'statusOptions'));
    }

    public function create(): View
    {
        $statusOptions = $this->statusOptions();

        return view('admin.articles.create', compact('statusOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:articles,slug'],
            'summary' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'status' => ['nullable', Rule::in(Article::STATUSES)],
            'published_at' => ['nullable', 'date'],
            'featured_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'author' => ['nullable', 'string', 'max:255'],
        ]);

        $data['status'] = $data['status'] ?? 'draft';
        $data['slug'] = Article::generateUniqueSlug($data['slug'] ?? $data['title']);
        $data['published_at'] = $this->normalizePublishedAt($data['published_at'] ?? null);

        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (empty($data['author'])) {
            $data['author'] = Auth::user()->name;
        }

        // Handle image upload
        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('articles', 'public');
        }

        Article::create($data);

        return redirect()->route('admin.articles.index')->with('success', 'Artikel berhasil dibuat.');
    }

    public function edit(Article $article): View
    {
        $statusOptions = $this->statusOptions();

        return view('admin.articles.edit', compact('article', 'statusOptions'));
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('articles', 'slug')->ignore($article->id)],
            'summary' => ['nullable', 'string'],
            'content' => ['sometimes', 'string'],
            'status' => ['sometimes', 'nullable', Rule::in(Article::STATUSES)],
            'published_at' => ['nullable', 'date'],
            'featured_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'author' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('status', $data)) {
            $data['status'] = $data['status'] ?? 'draft';
        }

        if (array_key_exists('published_at', $data)) {
            $data['published_at'] = $this->normalizePublishedAt($data['published_at']);
        }

        if (array_key_exists('slug', $data)) {
            $base = $data['slug'] ?: ($data['title'] ?? $article->title);
            $data['slug'] = Article::generateUniqueSlug($base, $article->id);
        } elseif (array_key_exists('title', $data) && empty($article->slug)) {
            $data['slug'] = Article::generateUniqueSlug($data['title'], $article->id);
        }

        if (isset($data['status']) && $data['status'] === 'published' && empty($data['published_at']) && empty($article->published_at)) {
            $data['published_at'] = now();
        }

        // Handle image removal
        if ($request->input('remove_image') && $article->featured_image) {
            Storage::disk('public')->delete($article->featured_image);
            $data['featured_image'] = null;
        }

        // Handle new image upload
        if ($request->hasFile('featured_image')) {
            // Delete old image if exists
            if ($article->featured_image) {
                Storage::disk('public')->delete($article->featured_image);
            }
            $data['featured_image'] = $request->file('featured_image')->store('articles', 'public');
        }

        $article->fill($data);
        if (empty($article->author)) {
            $article->author = Auth::user()->name;
        }
        $article->save();

        return redirect()->route('admin.articles.index')->with('success', 'Artikel berhasil diperbarui.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', 'Artikel berhasil dihapus.');
    }

    public function show(Article $article): View
    {
        return view('admin.articles.preview', compact('article'));
    }

    public function preview(Article $article): View
    {
        return view('admin.articles.preview', compact('article'));
    }

    private function statusOptions(): array
    {
        return collect(Article::STATUSES)
            ->map(fn (string $status) => [
                'value' => $status,
                'label' => ucfirst($status),
            ])->all();
    }

    private function normalizePublishedAt(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
