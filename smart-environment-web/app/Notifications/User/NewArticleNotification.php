<?php

namespace App\Notifications\User;

use App\Models\Article;
use App\Notifications\BaseNotification;

class NewArticleNotification extends BaseNotification
{
    protected string $category = 'article';
    protected string $icon = 'newspaper';
    protected string $color = 'info';

    public function __construct(protected Article $article)
    {
    }

    public function toArray(object $notifiable): array
    {
        return $this->buildData(
            type: 'new_article',
            title: 'Artikel Baru',
            message: "Ada artikel baru: \"{$this->article->title}\"",
            metadata: [
                'article_id' => $this->article->id,
                'article_title' => $this->article->title,
                'article_slug' => $this->article->slug ?? null,
                'article_thumbnail' => $this->article->thumbnail ?? null,
            ],
            actionUrl: "/articles/{$this->article->slug}"
        );
    }
}
