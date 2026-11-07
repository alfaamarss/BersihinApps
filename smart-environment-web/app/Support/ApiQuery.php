<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait ApiQuery
{
    protected function applyApiQuery(Builder $query, Request $request, array $filterable = [], array $sortable = []): Builder
    {
        $filters = (array) $request->query('filter', []);
        foreach ($filters as $field => $value) {
            if (in_array($field, $filterable, true) && $value !== '') {
                $query->where($field, 'like', '%'.$value.'%');
            }
        }

        $sort = $request->query('sort');
        if ($sort) {
            foreach (explode(',', $sort) as $field) {
                $dir = 'asc';
                if (str_starts_with($field, '-')) {
                    $dir = 'desc';
                    $field = ltrim($field, '-');
                }
                if (in_array($field, $sortable, true)) {
                    $query->orderBy($field, $dir);
                }
            }
        }

        return $query;
    }
}
