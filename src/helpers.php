<?php

use Kiqstyle\EloquentVersionable\VersioningDate;
use Kiqstyle\EloquentVersionable\VersioningTable;

if (!function_exists('versioningDate')) {
    /**
     * @return VersioningDate
     */
    function versioningDate()
    {
        return app('versioningDate');
    }
}

if (!function_exists('VersioningTable')) {
    /**
     * @return VersioningTable
     */
    function VersioningTable()
    {
        return app('VersioningTable');
    }
}
