<?php

namespace Rias\StatamicDataImport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Rias\StatamicDataImport\Jobs\ImportJob;
use Spatie\SimpleExcel\SimpleExcelReader;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\File;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Fields\Field;
use Statamic\Fieldtypes\Section;

class ImportController
{
    public function index()
    {
        return view('data-import::index');
    }

    public function targetSelect()
    {
        $collections = Collection::all()->map(function ($collection) {
            return [
                'label' => $collection->title(),
                'value' => $collection->handle(),
            ];
        })->sortBy('label')->values()->toArray();

        $sites = Site::all()->map(function (\Statamic\Sites\Site $site) {
            return [
                'label' => $site->name(),
                'value' => $site->handle(),
            ];
        })->sortBy('label')->values()->toArray();

        return view('data-import::target', compact('collections', 'sites'));
    }

    public function showData(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file'],
            'delimiter' => ['required'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');
        $path = $file->storeAs('data-import', 'data-import.csv');
        $path = storage_path('app/' . $path);
        $delimiter = request('delimiter', ',');

        $reader = SimpleExcelReader::create($path)
            ->useDelimiter($delimiter);

        $request->session()->put('data-import-path', $path);
        $request->session()->put('data-import-delimiter', $delimiter);

        $keys = array_keys($reader->getRows()->first());

        $request->session()->put('data-import-keys', $keys);

        return view('data-import::show', [
            'rowCount' => $reader->getRows()->count(),
            'preview' => $reader->getRows()->take(5)->toArray(),
        ]);
    }

    public function import(Request $request)
    {
        $handle = $request->get('collection');
        $collection = Collection::findByHandle($handle);

        /** @var \Statamic\Fields\Blueprint $blueprint */
        $blueprint = $collection->entryBlueprint();
        $fields = $blueprint->fields()
            ->resolveFields()
            ->reject(function (Field $field) {
                return in_array($field->type(), [Section::handle()]);
            })
            ->toArray();

        $request->session()->put('data-import-collection', $handle);
        $request->session()->put('data-import-site', request('site'));

        return view('data-import::import', [
            'keys' => $request->session()->get('data-import-keys'),
            'fields' => $fields,
        ]);
    }

    public function finalize(Request $request)
    {
        $path = $request->session()->get('data-import-path');
        $delimiter = $request->session()->get('data-import-delimiter');
        $arrayDelimiter = $request->get('array_delimiter', '|');
        $mapping = collect($request->get('mapping'))->filter();
        $collection = $request->session()->get('data-import-collection');
        $site = session()->get('data-import-site', Site::default()->handle());

        $uuid = Str::uuid()->toString();

        ImportJob::dispatch($uuid, $path, $site, $collection, $mapping, $delimiter, $arrayDelimiter);

        $request->session()->forget('data-import-path');
        $request->session()->forget('data-import-keys');
        $request->session()->forget('data-import-collection');
        $request->session()->forget('data-import-site');

        return redirect(cp_route('data-import.show', $uuid));
    }

    public function show(string $uuid)
    {
        return view('data-import::finalize', [
            'uuid' => $uuid,
        ]);
    }
}
