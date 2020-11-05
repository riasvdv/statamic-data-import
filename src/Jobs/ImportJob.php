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
        $reader->getRows()->each(function (array $row, int $index) use (&$failedRows, &$errors) {
            $mappedData = $this->mapping->map(function (string $rowKey) use ($row) {
                $value = explode($this->arrayDelimiter, $row[$rowKey]);

                if (count($value) === 1) {
                    return $value[0];
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
                ->slug(Str::slug($mappedData->get('title')))
                ->locale($this->site)
                ->collection($this->collection)
                ->data($mappedData);

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
}
