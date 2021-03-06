<?php

namespace Rias\StatamicDataImport;

use Statamic\Facades\CP\Nav;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class DataImportServiceProvider extends AddonServiceProvider
{
    protected $scripts = [
        __DIR__.'/../resources/dist/js/cp.js',
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    public function boot()
    {
        parent::boot();

        Statamic::booted(function () {
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'data-import');

            Nav::extend(function ($nav) {
                $nav->tools('Data Import')
                    ->route('data-import.index')
                    ->icon('upload')
                    ->active('data-import');
            });
        });
    }
}
