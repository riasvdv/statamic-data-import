@extends('statamic::layout')
@section('title', 'Import < Data Import')

@section('content')
    <form action="{{ cp_route('data-import.finalize') }}" method="POST">
        {{ csrf_field() }}

        <header class="mb-3">
            <div class="flex items-center justify-between">
                <h1>Map your data</h1>
                <button class="btn-primary">Import</button>
            </div>
        </header>

        <div class="card rounded p-3 lg:px-7 lg:py-5 shadow bg-white">
            <header class="text-center mb-6">
                <h1 class="mb-3">Map your data</h1>
                <p class="text-grey">Match your data with the fields of the collection.</p>
            </header>

            <div class="mb-5">
                <label for="array_delimiter" class="text-base mb-sm">Array Delimiter</label>
                <input id="array_delimiter" name="array_delimiter" placeholder="|" value="|" type="text" tabindex="1" class="input-text">
                <p class="text-grey">Set delimiter if one of your fields contains multiple entries. Defaults to pipe character <code>|</code></p>
            </div>

            <h2 class="mb-3">Fieldset Data</h2>
            <data-import
                name="mapping"
                id="mapping"
                :config="{
                    keys: {{ json_encode($keys) }},
                    fields: {{ json_encode($fields) }},
                }"
            ></data-import>
            @foreach ($errors as $error)
                <p class="text-red-500 mb-1">{{ $error }}</p>
            @endforeach
        </div>
    </form>
@endsection
