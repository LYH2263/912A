<?php

namespace App\Repositories;

use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TagRepository
{
    public function create(array $data): Tag
    {
        return Tag::create($data);
    }

    public function update(Tag $tag, array $data): Tag
    {
        $tag->update($data);
        return $tag->fresh();
    }

    public function delete(Tag $tag): ?bool
    {
        return $tag->delete();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Tag::withCount('products as product_count');

        if (isset($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        return $query->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage);
    }

    public function all(array $filters = []): Collection
    {
        $query = Tag::withCount('products as product_count')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc');

        if (isset($filters['ids']) && !empty($filters['ids'])) {
            $query->whereIn('id', $filters['ids']);
        }

        return $query->get();
    }

    public function find(int $id): ?Tag
    {
        return Tag::find($id);
    }

    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $query = Tag::where('name', $name);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
