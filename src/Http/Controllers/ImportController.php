<?php

namespace Rias\StatamicDataImport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\File;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;

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

        $reader = SimpleExcelReader::create($path)
            ->useDelimiter(request('delimiter', ','));

        $request->session()->put('data-import-path', $path);

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
        $fields = $blueprint->fields()->resolveFields()->toArray();

        $request->session()->put('data-import-collection', $handle);
        $request->session()->put('data-import-site', request('site'));

        return view('data-import::import', [
            'keys' => $request->session()->get('data-import-keys'),
            'fields' => $fields,
        ]);
    }

    public function finalize(Request $request)
    {
        $mapping = collect($request->get('mapping'))->filter();
        $arrayDelimiter = $request->get('array_delimiter', '|');
        $path = $request->session()->get('data-import-path');
        $reader = SimpleExcelReader::create($path)
            ->useDelimiter(request('delimiter', ','));
        $rowCount = $reader->getRows()->count();

        /** @var \Statamic\Entries\Collection $collection */
        $collection = Collection::findByHandle($request->session()->get('data-import-collection'));

        $failedRows = [];
        $errors = [];
        $reader->getRows()->each(function (array $row, int $index) use ($arrayDelimiter, $collection, $mapping, &$failedRows, &$errors) {
            $mappedData = $mapping->map(function (string $rowKey) use ($arrayDelimiter, $row) {
                $value = explode($arrayDelimiter, $row[$rowKey]);

                if (count($value) === 1) {
                    return $value[0];
                }

                return $value;
            });

            $title = $mappedData->get('title');
            if (! $title) {
                $failedRows[] = $row;
                $errors[] = "[Row {$index}]: No title.";
                return;
            }

            $entry = Entry::make()
                ->slug(Str::slug($mappedData->get('title')))
                ->locale(session()->get('data-import-site', Site::default()->handle()))
                ->collection($collection)
                ->data($mappedData);

            if ($collection->dated()) {
                $entry->date($mappedData->get('date', now()));
            }

            if (! $entry->save()) {
                $failedRows[] = $row;
                $errors[] = "[Row {$index}]: Failed to save.";
            }
        });

        $data = [
            'errors' => $errors,
            'rowCount' => $rowCount,
            'failedRows' => $failedRows,
        ];

        $request->session()->forget('data-import-path');
        $request->session()->forget('data-import-keys');
        $request->session()->forget('data-import-collection');
        $request->session()->forget('data-import-site');

        File::delete($path);

        Stache::refresh();

        return view('data-import::finalize', $data);
    }
}
