<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Stringable;

class FamilyModeController extends Controller
{
    public function __invoke()
    {
        $route = request()
            ->string('route')
            ->replace(['store', 'edit', 'update'], 'index')
            ->when(
                fn (Stringable $str) => $str->startsWith('family.'),
                fn (Stringable $str) => $str->remove('family.'),
                fn (Stringable $str) => $str->prepend('family.'),
            )
            ->value();

        if (Route::has($route)) {
            return redirect()->route($route);
        }

        return redirect()->route('home');
    }
}
