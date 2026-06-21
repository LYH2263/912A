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
        if ($this->repository->existsByName($data['name'])) {
            throw new \Exception('标签名称已存在，请使用其他名称');
        }

        return $this->repository->create($data);
    }

    public function update(Tag $tag, array $data): Tag
    {
        if (isset($data['name']) && $this->repository->existsByName($data['name'], $tag->id)) {
            throw new \Exception('标签名称已存在，请使用其他名称');
        }

        return $this->repository->update($tag, $data);
    }

    public function delete(Tag $tag): ?bool
    {
        return $this->repository->delete($tag);
    }
}
