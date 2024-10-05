<?php

namespace Kiqstyle\EloquentVersionable;

use Illuminate\Database\Eloquent\Model;

class VersioningPersistence
{
    public function createVersionedRecord(Model $model): void
    {
        self::getVersionedModel($model)->create($model->getAttributes());
    }

    public function updateNextColumnOfLastVersionedRegister(Model $model): void
    {
        $lastVersioned = self::getVersionedModel($model)
            ->withoutGlobalScopes()
            ->where('id', $model->id)
            ->orderBy('_id', 'desc')
            ->first();

        $lastVersioned->timestamps = false;

        $updateData = ['next' => $model->{$model->getUpdatedAtColumn()}];
        $lastVersioned->update($updateData);
    }

    public function createDeletedVersionedRecord(Model $model): void
    {
        $versionedInstance = self::getVersionedModel($model);
        $versionedInstance->fill($model->getAttributes());
        $versionedInstance->{$versionedInstance->getUpdatedAtColumn()} =
            $model->{$model->getUpdatedAtColumn()};
        $versionedInstance->{$versionedInstance->getDeletedAtColumn()} =
            $model->{$model->getUpdatedAtColumn()};
        $versionedInstance->save();
    }

    private static function getVersionedModel(Model $model): VersionedModel
    {
        $versionedClassName = $model->getVersioningModel();
        return new $versionedClassName();
    }
}
