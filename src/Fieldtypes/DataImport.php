<?php

namespace Rias\StatamicDataImport\Fieldtypes;

use Statamic\Fields\Fieldtype;

class DataImport extends Fieldtype
{
    public function defaultValue(): ?array
    {
        return null;
    }

    public function preProcess($data)
    {
        return $data;
    }

    public function process($data)
    {
        unset($data['uploaded_data_keys']);
        unset($data['selected_collection_fields']);

        return $data;
    }
}
