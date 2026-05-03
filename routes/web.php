<?php

use App\Livewire\CsvEditor;
use Illuminate\Support\Facades\Route;

Route::livewire('/', CsvEditor::class)->name('csv-editor');
