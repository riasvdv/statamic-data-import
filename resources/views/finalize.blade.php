@extends('statamic::layout')
@section('title', 'Done < Data Import')

@section('content')
    <form action="{{ cp_route('data-import.finalize') }}" method="POST">
        {{ csrf_field() }}

        <header class="mb-3">
            <h1>Import done</h1>
        </header>

        <div class="card rounded p-3 lg:px-7 lg:py-5 shadow bg-white">
            <header class="text-center">
                <h1 class="mb-3">Import done</h1>
                <p class="text-grey">From <strong>{{ $rowCount }}</strong> uploaded rows of data <strong>{{ $rowCount - count($failedRows) }}</strong> rows have been imported.</p>
            </header>

            @if (count($errors))
                <div class="mb-4 mt-6">
                    <h2 class="mb-1">Errors</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Message</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($errors as $index => $error)
                                <tr>
                                    <td class="text-red mb-1">{{ $error }}</td>
                                    <td class="text-red mb-1">{{ implode (", ", $failedRows[$index]) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </form>
@endsection
