<?php

namespace Xetaio\Counts\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasRelatedCount
{
    protected static function bootHasRelatedCount(): void
    {
        static::created(function ($model) {
            $model->incrementRelatedCountsOnCreate();
        });

        static::deleted(function ($model) {
            $model->decrementRelatedCountsOnDelete();
        });

        static::updated(function ($model) {
            $model->syncRelatedCountsOnUpdate();
        });
    }

    protected static function getCountedRelations(): array
    {
        return static::$countedRelations ?? [];
    }

    protected function incrementRelatedCountsOnCreate(): void
    {
        foreach (static::getCountedRelations() as $relation => $column) {
            $this->incrementParentCount($relation, $column);
        }
    }

    protected function decrementRelatedCountsOnDelete(): void
    {
        foreach (static::getCountedRelations() as $relation => $column) {
            $this->decrementParentCount($relation, $column);
        }
    }

    protected function syncRelatedCountsOnUpdate(): void
    {
        foreach (static::getCountedRelations() as $relation => $column) {
            $this->syncParentCountOnRelationChange($relation, $column);
        }
    }

    protected function incrementParentCount(string $relationName, string $column): void
    {
        $parent = $this->getBelongsToParent($relationName);
        if ($parent) {
            $parent->increment($column);
        }
    }

    protected function decrementParentCount(string $relationName, string $column): void
    {
        $parent = $this->getBelongsToParent($relationName);
        if ($parent && (int) $parent->{$column} > 0) {
            $parent->decrement($column);
        }
    }

    protected function syncParentCountOnRelationChange(string $relationName, string $column): void
    {
        $relation = $this->getBelongsToRelation($relationName);
        if (! $relation) {
            return;
        }

        $foreign = $relation->getForeignKeyName();

        $oldId = $this->getOriginal($foreign);
        $newId = $this->{$foreign};

        if ($oldId === $newId) {
            return;
        }

        $parentClass = get_class($relation->getRelated());

        if ($oldId) {
            $parentClass::whereKey($oldId)->decrement($column);
        }
        if ($newId) {
            $parentClass::whereKey($newId)->increment($column);
        }
    }

    protected function getBelongsToParent(string $relationName)
    {
        $relation = $this->getBelongsToRelation($relationName);

        return $relation?->getResults();
    }

    protected function getBelongsToRelation(string $relationName): ?BelongsTo
    {
        if (! method_exists($this, $relationName)) {
            return null;
        }

        $relation = $this->{$relationName}();

        return $relation instanceof BelongsTo ? $relation : null;
    }
}
