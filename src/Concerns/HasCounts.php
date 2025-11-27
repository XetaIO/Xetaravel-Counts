<?php

namespace Xetaio\Counts\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasCounts
{
    /**
     * Handle Model events.
     */
    protected static function bootHasCounts(): void
    {
        static::created(function ($model) {
            $model->incrementRelatedCountsOnCreateOrRestore();
        });

        static::deleted(function ($model) {
            $model->decrementRelatedCountsOnDelete();
        });

        static::updated(function ($model) {
            $model->syncRelatedCountsOnUpdate();
        });

        // We must also handle restored event for softdelete model.
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(function ($model) {
                $model->incrementRelatedCountsOnCreateOrRestore();
            });
        }
    }

    /**
     * Get the relations of the model.
     */
    protected static function getCountsConfig(): array
    {
        return static::$countsConfig ?? [];
    }

    /**
     * For each relation(s), increment the related parent count field.
     */
    protected function incrementRelatedCountsOnCreateOrRestore(): void
    {
        foreach (static::getCountsConfig() as $relation => $column) {
            $this->incrementParentCount($relation, $column);
        }
    }

    /**
     * For each relation(s), decrement the related parent count field.
     */
    protected function decrementRelatedCountsOnDelete(): void
    {
        foreach (static::getCountsConfig() as $relation => $column) {
            $this->decrementParentCount($relation, $column);
        }
    }

    /**
     * For each relation(s), synchronize the related parent count field.
     */
    protected function syncRelatedCountsOnUpdate(): void
    {
        foreach (static::getCountsConfig() as $relation => $column) {
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
