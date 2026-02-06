<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Switch the application language.
     *
     * @param string $locale
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switch($locale)
    {
        if (in_array($locale, ['en', 'fr'])) {
            session(['locale' => $locale]);
        }
        return redirect()->back();
    }
}
