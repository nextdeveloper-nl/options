<?php

namespace NextDeveloper\Options\Services;

use Illuminate\Support\Facades\File;

class ChangelogService
{
    private static function path(): string
    {
        return storage_path('app/options/changelog.json');
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

    public static function record(string $uri, string $method, array $changes): void
    {
        $data = self::load();
        array_unshift($data, [
            'uri'        => $uri,
            'method'     => strtoupper($method),
            'changed_at' => now()->toIso8601ZuluString(),
            'changes'    => $changes,
        ]);
        self::save($data);
    }

    public static function getForRoute(string $uri, string $method): array
    {
        $method = strtoupper($method);
        return array_values(array_filter(self::load(), function ($entry) use ($uri, $method) {
            return $entry['uri'] === $uri && $entry['method'] === $method;
        }));
    }

    public static function all(int $limit = 100): array
    {
        return array_slice(self::load(), 0, $limit);
    }
}
