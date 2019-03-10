<?php

namespace Cohrosonline\EloquentVersionable;

use Illuminate\Database\Eloquent\Scope;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VersionableScope implements Scope
{
    /**
     * Apply scope on the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder  $builder
     * @param \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (versioningDate()->issetDate() && ($model->isVersioningEnabled() === true)) {
            $datetime = versioningDate()->getDate()->format('Y-m-d H:i:s');

            $builder->where($model->getVersioningTable() . '.' . $model->getUpdatedAtColumn(), '<=', $datetime)
                ->whereNull($model->getVersioningTable() . '.deleted_at')
                ->where(function (Builder $q) use ($datetime, $model) {
                    $q->where($model->getQualifiedNxtColumn(), '>', $datetime);
                    $q->orWhereNull($model->getQualifiedNxtColumn());
                });

//            $joins = $builder->getQuery()->joins ?? [];
//            if (count($joins) > 0) {
//                foreach ($joins as $join) {
//                    if (strpos($join->table, '_versioning') !== false) {
//                        // @todo change to modified_at and deleted_at of model
//                        $builder->where($join->table . '.modified_at', '<=', $datetime)
//                            ->whereNull($join->table . '.deleted_at')
//                            ->where(function (Builder $q) use ($datetime, $join) {
//                                $q->where($join->table . '.next', '>', $datetime);
//                                $q->orWhereNull($join->table . '.next');
//                            });
//                    }
//                }
//            }
        }
    }
}
