<?php

namespace App\Services;

use App\Models\Tag;
use App\Repositories\TagRepository;

class TagService
{
    public function __construct(
        private TagRepository $repository
    ) {
    }

    public function create(array $data): Tag
    {
        // 先查含软删除的同名标签
        $existing = $this->repository->findByNameWithTrashed($data['name']);

        if ($existing) {
            if ($existing->trashed()) {
                // 已软删除的同名标签：复活并更新其他字段
                return $this->repository->restoreAndUpdate($existing, $data);
            }
            // 未删除的同名标签：报错
            throw new \Exception('标签名称已存在，请使用其他名称');
        }

        return $this->repository->create($data);
    }

    public function update(Tag $tag, array $data): Tag
    {
        if (isset($data['name'])) {
            // 名称变更时，按含软删除的查，避免唯一键冲突
            $existing = $this->repository->findByNameWithTrashed($data['name']);
            if ($existing && $existing->id !== $tag->id) {
                throw new \Exception('标签名称已存在，请使用其他名称');
            }
        }

        return $this->repository->update($tag, $data);
    }

    public function delete(Tag $tag): ?bool
    {
        return $this->repository->delete($tag);
    }
}
