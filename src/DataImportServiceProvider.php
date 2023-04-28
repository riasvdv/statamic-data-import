<?php

namespace Rias\StatamicDataImport;

use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
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

            Nav::extend(function (\Statamic\CP\Navigation\Nav $nav) {
                $nav->tools('Data Import')
                    ->route('data-import.index')
                    ->icon('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M336 192H480V480H160V368H128V480v32h32H480h32V480 152L360 0H160 128V32 256h32V32H320V176v16h16zm138.7-32H352V37.3L474.7 160zM283.3 212.7L272 201.4 249.4 224l11.3 11.3L329.4 304H16 0v32H16 329.4l-68.7 68.7L249.4 416 272 438.6l11.3-11.3 96-96L390.6 320l-11.3-11.3-96-96z"/></svg>')
                    ->can('use data import')
                    ->active('data-import');
            });

            Permission::group('data-import', 'Data Import', function () {
                Permission::register('use data import');
            });
        });
    }
}
