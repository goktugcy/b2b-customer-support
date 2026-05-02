<?php

namespace App\Services\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PostgresFullTextSearch
{
    public function apply(Builder $query, array $columns, string $term, string $boolean = 'and'): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        if (! $this->enabled()) {
            return $this->applyLike($query, $columns, $term, $boolean);
        }

        $vector = collect($columns)
            ->map(fn (string $column): string => "coalesce({$column}::text, '')")
            ->implode(" || ' ' || ");

        return $query->whereRaw(
            "to_tsvector('simple', {$vector}) @@ websearch_to_tsquery('simple', ?)",
            [$term],
            $boolean,
        );
    }

    public function enabled(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    private function applyLike(Builder $query, array $columns, string $term, string $boolean): Builder
    {
        $callback = function (Builder $query) use ($columns, $term): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $query->{$method}($column, 'like', '%'.$term.'%');
            }
        };

        return $boolean === 'or'
            ? $query->orWhere($callback)
            : $query->where($callback);
    }
}
