<?php

namespace Xetaio\Counts\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

trait HasBelongsToManyCounts
{
    protected static function bootHasBelongsToManyCounts(): void
    {
        static::created(function (Pivot $pivot) {
            $pivot->incrementBelongsToManyCounts();
        });

        static::deleted(function (Pivot $pivot) {
            $pivot->decrementBelongsToManyCounts();
        });
    }

    protected static function getCountsConfig(): array
    {
        return static::$countsConfig ?? [];
    }

    protected function incrementBelongsToManyCounts(): void
    {
        foreach (static::getCountsConfig() as $relation => $column) {
            $this->incrementParent($relation, $column);
        }
    }

    protected function decrementBelongsToManyCounts(): void
    {
        foreach (static::getCountsConfig() as $relation => $column) {
            $this->decrementParent($relation, $column);
        }
    }

    protected function incrementParent(string $relationName, string $column): void
    {
        $parent = $this->getBelongsToParent($relationName);
        if ($parent) {
            $parent->increment($column);
        }
    }

    protected function decrementParent(string $relationName, string $column): void
    {
        $parent = $this->getBelongsToParent($relationName);
        if ($parent && (int) $parent->{$column} > 0) {
            $parent->decrement($column);
        }
    }

    protected function getBelongsToParent(string $relationName): ?Model
    {
        if (! method_exists($this, $relationName)) {
            return null;
        }

        $relation = $this->{$relationName}();

        if ($relation instanceof BelongsTo) {
            return $relation->getResults();
        }

        return null;
    }
}
