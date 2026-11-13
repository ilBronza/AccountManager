<?php

namespace IlBronza\AccountManager\Routing;

use Composer\InstalledVersions;
use Illuminate\Routing\Route;
use UnexpectedValueException;

class RouteDescriptions
{
    private array $packages = [];
    private array $application = [];

    /** Reload once per sync, rather than once per route or for the process lifetime. */
    public function load(): void
    {
        $packages = [];
        $sources = [];
        foreach ($this->packageFiles() as $file) {
            $descriptions = (static fn (string $path) => require $path)($file);
            foreach ($this->validate($descriptions, $file) as $name => $description) {
                if (isset($packages[$name]) && $packages[$name] !== $description) {
                    throw new UnexpectedValueException("Conflicting route description for [{$name}] in [{$sources[$name]}] and [{$file}].");
                }
                $packages[$name] = $description;
                $sources[$name] = $file;
            }
        }

        // Laravel loads this file into config, including when config:cache is used.
        $application = $this->validate(config('route-descriptions', []), config_path('route-descriptions.php'));
        $this->packages = $packages;
        $this->application = $application;
    }

    public function forRoute(Route $route): ?string
    {
        $name = $route->getName();
        if ($name !== null && isset($this->application[$name])) return $this->application[$name];

        $metadata = method_exists($route, 'getMetadata') ? $route->getMetadata('description') : null;

        return $this->text($metadata)
            ?? $this->text($route->getAction('description'))
            ?? ($name !== null ? ($this->packages[$name] ?? null) : null);
    }

    /** Composer paths also cover symlinked path repositories and custom vendor directories. */
    protected function packageFiles(): array
    {
        $files = [__DIR__.'/../../config/route-descriptions.php'];
        $names = InstalledVersions::getInstalledPackages();
        sort($names, SORT_STRING);
        foreach ($names as $name) {
            $path = InstalledVersions::getInstallPath($name);
            if ($path !== null && realpath($path) !== realpath(base_path())) {
                $files[] = $path.'/config/route-descriptions.php';
            }
        }

        $existing = [];
        foreach ($files as $file) {
            if (is_file($file)) $existing[realpath($file)] = realpath($file);
        }

        return array_values($existing);
    }

    private function validate(mixed $descriptions, string $source): array
    {
        if (! is_array($descriptions)) {
            throw new UnexpectedValueException("Route descriptions in [{$source}] must return an array.");
        }
        foreach ($descriptions as $name => $description) {
            if (! is_string($name) || trim($name) === '' || $this->text($description) === null) {
                throw new UnexpectedValueException("Route descriptions in [{$source}] must map exact route names to non-empty strings.");
            }
            $descriptions[$name] = trim($description);
        }

        return $descriptions;
    }

    private function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
