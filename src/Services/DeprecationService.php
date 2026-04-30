<?php

namespace NextDeveloper\Options\Services;

use Illuminate\Support\Facades\File;

class DeprecationService
{
    private static function path(): string
    {
        return storage_path('app/options/deprecations.json');
    }

    private static function load(): array
    {
        $path = self::path();
        if (!File::exists($path)) {
            return [];
        }
        return json_decode(File::get($path), true) ?? [];
    }

    private static function save(array $data): void
    {
        $dir = storage_path('app/options');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }
        File::put(self::path(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public static function deprecate(string $uri, string $method, string $note, ?string $sunsetDate = null): void
    {
        $data = self::load();
        $key = strtoupper($method) . ':' . $uri;
        $data[$key] = [
            'uri'          => $uri,
            'method'       => strtoupper($method),
            'deprecated_at' => now()->toDateString(),
            'sunset_date'  => $sunsetDate,
            'note'         => $note,
        ];
        self::save($data);
    }

    public static function undeprecate(string $uri, string $method): void
    {
        $data = self::load();
        $key = strtoupper($method) . ':' . $uri;
        unset($data[$key]);
        self::save($data);
    }

    public static function isDeprecated(string $uri, string $method): bool
    {
        $data = self::load();
        $key = strtoupper($method) . ':' . $uri;
        return isset($data[$key]);
    }

    public static function get(string $uri, string $method): ?array
    {
        $data = self::load();
        $key = strtoupper($method) . ':' . $uri;
        return $data[$key] ?? null;
    }

    public static function all(): array
    {
        return self::load();
    }
}
