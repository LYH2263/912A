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
        // 手动清除多对多关联（软删除不会触发外键级联）
        $tag->products()->detach();

        return $tag->delete();
    }

    /**
     * 按名称查询（包含已软删除的）
     */
    public function findByNameWithTrashed(string $name): ?Tag
    {
        return Tag::withTrashed()->where('name', $name)->first();
    }

    /**
     * 复活已软删除的标签，并更新其他字段
     */
    public function restoreAndUpdate(Tag $tag, array $data): Tag
    {
        $tag->restore();
        $tag->update($data);
        return $tag->fresh();
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
