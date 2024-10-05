<?php

namespace Kiqstyle\EloquentVersionable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class VersionableScope implements Scope
{
    /**
     * Apply scope on the query.
     */
    public function apply(Builder $builder, Model|Versionable $model): void
    {
        if (
            ! versioningDate()->issetDate() ||
            ($model->isVersioningEnabled() !== true)
        ) {
            return;
        }

        $datetime = versioningDate()->getDate()->format('Y-m-d H:i:s');

        $updatedAt = $model->getUpdatedAtColumn();
        $next = $model->getQualifiedNxtColumn();
        $versioningTable = $model->getVersioningTable();
        $builder->where($versioningTable . '.' . $updatedAt, '<=', $datetime)
            ->where(
                fn (Builder $q) => $q
                    ->where($next, '>', $datetime)
                    ->orWhereNull($next)
            );

        $joins = $builder->getQuery()->joins ?? [];
        foreach ($joins as $join) {
            if (str_contains($join->table, '_versioning')) {
                $table = $join->table;
                $fieldUpdatedAt = $table . '.' . $updatedAt;
                $fieldDeletedAt = $table . '.' . $model->getDeletedAtColumn();
                $fieldNext = $table . '.' . $model->getNextColumn();
                $isNextAfterDateOrIsNextNull = fn (Builder $q) => $q
                    ->where($fieldNext, '>', $datetime)
                    ->orWhereNull($fieldNext);

                $builder->where($fieldUpdatedAt, '<=', $datetime)
                    ->whereNull($fieldDeletedAt)
                    ->where($isNextAfterDateOrIsNextNull);
            }
        }
    }
}
