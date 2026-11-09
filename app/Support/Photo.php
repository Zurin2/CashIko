<?php

namespace App\Support;

class Photo
{
    /** Private files: short-lived signed URL, created at render time. */
    public static function signedUrl(?string $path, int $minutes = 60): ?string
    {
        if (! $path || ! \Storage::disk('private')->exists($path)) {
            return null;
        }

        return \URL::temporarySignedRoute('photo.view', now()->addMinutes($minutes), ['path' => $path]);
    }
}
