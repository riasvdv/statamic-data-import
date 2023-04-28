@extends('statamic::layout')
@section('title', 'Select Target < Data Import')

@section('content')
    <form action="{{ cp_route('data-import.import') }}" method="post">
        {{ csrf_field() }}

        <header class="mb-3">
            <div class="flex items-center justify-between">
                <h1>Select target</h1>
                <button class="btn-primary">Continue</button>
            </div>
        </header>

        <div class="card rounded p-3 lg:px-7 lg:py-5 shadow bg-white">
            <header class="text-center mb-6">
                <h1 class="mb-3">Select target</h1>
                <p class="text-grey">Select a type to where the data must be imported. Users / Collection</p>
            </header>

            <input type="hidden" ref="type" name="type">
            <input type="hidden" ref="collection" name="collection">
            <input type="hidden" ref="site" name="site">

            <div class="w-1/2 pr-2 mb-4">
                <label class="font-bold text-base mb-sm">Type</label>

                <v-select
                    class="w-full"
                    :clearable="false"
                    :options="{{ json_encode([
                        ['value' => 'users', 'label' => 'Users'],
                        ['value' => 'collection', 'label' => 'Collection'],
                    ]) }}"
                    :reduce="selection => selection.value"
                    @input="(value) => {
                        this.$refs.type.value = value;
                        document.querySelector('#collection').style.display = 'none';
                        document.querySelector('#users').style.display = 'none';

                        if (value === 'collection') {
                            document.querySelector('#collection').style.display = 'flex';
                        }

                        if (value === 'collection') {
                            document.querySelector('#users').style.display = 'block';
                        }
                    }"
                />
            </div>

            <div id="collection" style="display: none">
                <div class="w-1/2 mr-2">
                    <label class="font-bold text-base mb-sm">Site</label>
                    <v-select
                        class="w-full"
                        :options="{{ json_encode($sites) }}"
                        :reduce="selection => selection.value"
                        @input="(value) => this.$refs.site.value = value"
                    />
                </div>
                <div class="w-1/2 ml-2">
                    <label class="font-bold text-base mb-sm">Collection</label>
                    <v-select
                        class="w-full"
                        :options="{{ json_encode($collections) }}"
                        :reduce="selection => selection.value"
                        @input="(value) => this.$refs.collection.value = value"
                    />
                </div>
            </div>

            <div id="users">

            </div>
        </div>
    </form>
@endsection
