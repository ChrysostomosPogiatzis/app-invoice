<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Storage;

trait ResolvesStoredFile
{
    protected function normalizeStoredPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $normalized = parse_url($path, PHP_URL_PATH) ?: $path;
        $normalized = preg_replace('#^/storage/#', '', $normalized);
        $normalized = preg_replace('#^storage/#', '', $normalized);

        return ltrim($normalized, '/');
    }

    protected function storedFileDisk(?string $path): ?string
    {
        $normalized = $this->normalizeStoredPath($path);

        if (! $normalized) {
            return null;
        }

        if (Storage::disk('local')->exists($normalized)) {
            return 'local';
        }

        if (Storage::disk('public')->exists($normalized)) {
            return 'public';
        }

        return null;
    }

    protected function storedFileAbsolutePath(?string $path): ?string
    {
        $normalized = $this->normalizeStoredPath($path);
        $disk = $this->storedFileDisk($path);

        if (! $normalized || ! $disk) {
            return null;
        }

        return Storage::disk($disk)->path($normalized);
    }
}
