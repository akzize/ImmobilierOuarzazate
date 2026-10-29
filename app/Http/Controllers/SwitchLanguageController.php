<?php

namespace App\Http\Controllers;

use App\Enums\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The language switch in the admin user menu: saves the choice on the account, then returns to the same page.
 */
class SwitchLanguageController extends Controller
{
    public function __invoke(Request $request, Language $language): RedirectResponse
    {
        $request->user()->update(['locale' => $language]);

        return back(fallback: filament()->getUrl());
    }
}
