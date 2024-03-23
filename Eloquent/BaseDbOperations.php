<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Astrotech\Core\Base\Domain\Contracts\Entity;

trait BaseDbOperations
{
    abstract protected function eloquentModel(): ModelBase;

    public function query(): Builder
    {
        return $this->eloquentModel()->newQuery();
    }

    public function create(Entity $entity): string|int
    {
        $this->eloquentModel()->fill($entity->prepare());
        $this->eloquentModel()->exists = false;
        $this->eloquentModel()->save();
        return $this->eloquentModel()->getKey();
    }

    public function read(string|int $id, string $entityClassName): ?Entity
    {
        $record = $this->eloquentModel()::find($id);

        if (!$record) {
            return null;
        }

        return new $entityClassName($record->toArray());
    }

    public function update(Entity $entity): bool
    {
        $this->eloquentModel()->fill($entity->prepare());
        $this->eloquentModel()->exists = true;
        return $this->eloquentModel()->save();
    }

    public function delete(string|int $id): bool
    {
        $record = $this->eloquentModel()::find($id);

        if (!$record) {
            return false;
        }

        return $record->delete();
    }

    public function beginTransaction(): self
    {
    }

    public function commit(): void
    {
    }

    public function rollback(): void
    {
    }
}
