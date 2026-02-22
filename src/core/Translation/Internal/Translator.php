<?php

namespace Core\Translation\Internal;

class Translator
{
    /**
     * Loaded translation lines, keyed by locale.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $loaded = [];

    public function __construct(
        protected string $langPath,
        protected string $locale = 'en',
        protected string $fallbackLocale = 'en'
    ) {}

    /**
     * Get the translation for the given key.
     *
     * Supports:
     *  - Simple keys:           'welcome'
     *  - Nested dot-notation:  'auth.failed'
     *
     * @param string $key
     * @param array<string, string> $replace
     * @param string|null $locale
     * @return string|array|null
     */
    public function get(string $key, array $replace = [], ?string $locale = null): string|array|null
    {
        $locale ??= $this->locale;

        $line = $this->getLine($key, $locale)
            ?? $this->getLine($key, $this->fallbackLocale)
            ?? $key;

        if (is_string($line) && !empty($replace)) {
            foreach ($replace as $placeholder => $value) {
                $line = str_replace(':' . $placeholder, (string) $value, $line);
                $line = str_replace(':' . ucfirst($placeholder), ucfirst((string) $value), $line);
                $line = str_replace(':' . strtoupper($placeholder), strtoupper((string) $value), $line);
            }
        }

        return $line;
    }

    /**
     * Get the active locale.
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Set the active locale.
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    // -------------------------------------------------------------------------

    /**
     * Resolve a single translation line from the loaded messages.
     */
    protected function getLine(string $key, string $locale): string|array|null
    {
        $this->loadLocale($locale);

        $messages = $this->loaded[$locale] ?? [];

        // Direct key lookup
        if (array_key_exists($key, $messages)) {
            return $messages[$key];
        }

        // Dot-notation lookup
        $segments = explode('.', $key);
        $value = $messages;
        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Load the translation file for a locale (once).
     */
    protected function loadLocale(string $locale): void
    {
        if (isset($this->loaded[$locale])) {
            return;
        }

        $file = rtrim($this->langPath, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $locale . '.php';

        if (file_exists($file)) {
            $this->loaded[$locale] = require $file;
        } else {
            $this->loaded[$locale] = [];
        }
    }
}
