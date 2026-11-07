<?php

namespace App\Http\Controllers\API\Mobile\Resident;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    use ApiResponse;

    /**
     * Get list of published articles
     */
    public function index(Request $request)
    {
        $query = Article::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

        // Search by title or content
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'published_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if (in_array($sortBy, ['published_at', 'title', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->input('per_page', 10);
        $articles = $query->paginate($perPage);

        $data = $articles->getCollection()->map(function ($article) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'summary' => strip_tags($article->summary),
                'featured_image_url' => $article->featured_image_url,
                'author' => $article->author,
                'published_at' => $article->published_at?->toIso8601String(),
                'estimated_reading_time' => $article->estimated_reading_time,
            ];
        });

        return $this->ok([
            'articles' => $data,
            'meta' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ],
        ]);
    }

    /**
     * Get single article detail
     */
    public function show(Request $request, string $id)
    {
        // Support both id and slug
        $article = Article::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($q) use ($id) {
                $q->where('id', $id)
                  ->orWhere('slug', $id);
            })
            ->first();

        if (!$article) {
            return $this->error('Article not found or not published yet.', 404);
        }

        return $this->ok([
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'summary' => $article->summary,
                'content' => $article->content,
                'featured_image_url' => $article->featured_image_url,
                'author' => $article->author,
                'published_at' => $article->published_at?->toIso8601String(),
                'created_at' => $article->created_at?->toIso8601String(),
                'updated_at' => $article->updated_at?->toIso8601String(),
                'estimated_reading_time' => $article->estimated_reading_time,
            ],
        ]);
    }

    /**
     * Get featured/latest articles (for homepage)
     */
    public function featured(Request $request)
    {
        $limit = $request->input('limit', 5);

        $articles = Article::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get()
            ->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'summary' => strip_tags($article->summary),
                    'featured_image_url' => $article->featured_image_url,
                    'author' => $article->author,
                    'published_at' => $article->published_at?->toIso8601String(),
                    'estimated_reading_time' => $article->estimated_reading_time,
                ];
            });

        return $this->ok([
            'articles' => $articles,
        ]);
    }
}
