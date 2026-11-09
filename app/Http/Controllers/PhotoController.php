<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Serves a privately stored product photo through a signed URL. */
class PhotoController extends Controller
{
    public function view(Request $request, string $path)
    {
        if (! $request->hasValidSignature()) {
            abort(302);
        }

        if (! \Storage::disk('private')->exists($path)) {
            abort(404);
        }

        return \Storage::disk('private')->response($path);
    }
}
