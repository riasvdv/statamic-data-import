<?php

namespace Rias\StatamicDataImport\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Entry;
use Statamic\Facades\File;
use Statamic\Facades\Stache;
use Statamic\Fields\Field;
use Statamic\Fieldtypes\Arr as ArrFieldtype;
use Statamic\Fieldtypes\Taggable;
use Statamic\Fieldtypes\Toggle;
use Statamic\Support\Arr;

class ImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var string */
    private $uuid;

    /** @var string */
    private $path;

    /** @var string */
    private $site;

    /** @var \Statamic\Entries\Collection */
    private $collection;

    /** @var string */
    private $delimiter;

    /** @var \Illuminate\Support\Collection */
    private $mapping;

    /** @var string */
    private $arrayDelimiter;

    public function __construct(
        string $uuid,
        string $path,
        string $site,
        string $collection,
        Collection $mapping,
        string $delimiter = ',',
        string $arrayDelimiter = '|'
    ) {
        $this->uuid = $uuid;
        $this->path = $path;
        $this->site = $site;
        $this->collection = CollectionFacade::findByHandle($collection);
        $this->delimiter = $delimiter;
        $this->mapping = $mapping;
        $this->arrayDelimiter = $arrayDelimiter;
    }

    public function handle()
    {
        $reader = SimpleExcelReader::create($this->path)->useDelimiter($this->delimiter);
        $rowCount = $reader->getRows()->count();

        cache()->put("{$this->uuid}-total", $rowCount);
        cache()->put("{$this->uuid}-processed", 0);

        $failedRows = [];
        $errors = [];

        $blueprint = $this->collection->entryBlueprint();
        /** @var Collection $fields */
        $fields = $blueprint->fields()->resolveFields();

        $reader->getRows()->each(function (array $row, int $index) use (&$failedRows, &$errors, $fields) {
            $mappedData = $this->mapping->map(function (string $rowKey, string $fieldKey) use ($row, $fields) {
                $value = trim($row[$rowKey]);
                $value = explode($this->arrayDelimiter, $value);
                $value = count($value) === 1 ? $value[0] : $value;

                /** @var ?Field $field */
                $field = $fields->first(function (Field $field) use ($fieldKey) {
                    return $field->handle() === $fieldKey;
                });

                if ($field->type() === Toggle::handle()) {
                    $value = $this->toBool($value) ?? $value;
                }

                if (in_array($field->type(), [Taggable::handle(), ArrFieldtype::handle()])) {
                    $value = Arr::wrap($value);
                }

                return $value;
            });

            $title = $mappedData->get('title');
            if (! $title) {
                $failedRows[] = $row;
                $errors[] = "[Row {$index}]: This row has no title.";

                return;
            }

            $entry = Entry::make()
                ->locale($this->site)
                ->collection($this->collection)
                ->data(Arr::removeNullValues($mappedData->all()));

            if ($this->collection->requiresSlugs()) {
                $entry->slug(Str::slug($mappedData->get('title')));
            }

            if ($this->collection->dated()) {
                $entry->date($mappedData->get('date', now()));
            }

            if (! $entry->save()) {
                $failedRows[] = $row;
                $errors[] = "[Row {$index}]: Failed to save.";
            }

            cache()->increment("{$this->uuid}-processed");
        });

        cache()->put("{$this->uuid}-errors", $errors);
        cache()->put("{$this->uuid}-failed", $failedRows);

        File::delete($this->path);

        Stache::clear();
    }

    private function toBool($variable): ?bool
    {
        if (!isset($variable)) return null;
        return filter_var($variable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
