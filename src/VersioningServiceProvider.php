<?php

namespace Cohrosonline\EloquentVersionable;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class VersioningServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('versioningDate', function () {
            return (new VersioningDate())->setDate(Carbon::now()->addDay());
        });

        include 'helpers.php';
    }
}
