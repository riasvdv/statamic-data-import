<?php

use Rias\StatamicDataImport\Http\Controllers\ImportController;

Route::get('data-import', ['\\'. ImportController::class, 'index'])->name('data-import.index');
Route::post('data-import/target', ['\\'. ImportController::class, 'targetSelect'])->name('data-import.target-select');
Route::get('data-import/target', ['\\'. ImportController::class, 'targetSelect'])->name('data-import.target-select');
Route::post('data-import/show', ['\\'. ImportController::class, 'showData'])->name('data-import.show-data');
Route::post('data-import/import', ['\\'. ImportController::class, 'import'])->name('data-import.import');
Route::post('data-import/finalize', ['\\'. ImportController::class, 'finalize'])->name('data-import.finalize');
