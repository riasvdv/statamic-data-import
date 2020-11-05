@extends('statamic::layout')
@section('title', 'Done < Data Import')

@php($status = !is_null(cache("{$uuid}-total")) && cache("{$uuid}-total") === cache("{$uuid}-processed") ? 'done' : 'ongoing')
@section('content')
    <form action="{{ cp_route('data-import.finalize') }}" method="POST">
        {{ csrf_field() }}

        <header class="mb-3">
            <h1>Import {{ $status }}</h1>
        </header>

        <div class="card rounded p-3 lg:px-7 lg:py-5 shadow bg-white">
            <header class="text-center">
                <h1 class="mb-3">Import {{ $status }}</h1>
                @if (is_null(cache("{$uuid}-total")))
                    <p class="text-grey">Your import is being processed...</p>
                @else
                    <p class="text-grey">From <strong>{{ cache("{$uuid}-total") }}</strong> uploaded rows of data <strong>{{ cache("{$uuid}-processed") }}</strong> rows have been imported.</p>
                @endif
                @if ($status === 'ongoing')
                    <button type="button" class="btn-primary mt-2" onclick="window.location.reload()">Refresh</button>
                @endif
            </header>

            @if (count(cache("{$uuid}-errors") ?? []))
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
                            @foreach (cache("{$uuid}-errors") as $index => $error)
                                <tr>
                                    <td class="text-red mb-1">{{ $error }}</td>
                                    <td class="text-red mb-1">{{ implode (", ", cache("{$uuid}-failed")[$index]) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </form>
@endsection
