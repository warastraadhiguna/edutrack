<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route('filament.admin.pages.dashboard'));
