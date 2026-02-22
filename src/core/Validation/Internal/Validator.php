<?php

namespace Core\Validation\Internal;

use Core\Database\Contracts\DatabaseManagerInterface;
use Respect\Validation\Validator as v;
use Core\Validation\Contracts\ValidatorInterface;
use Core\Container\Internal\Application;

/**
 * @internal
 */
class Validator implements ValidatorInterface
{
    /** @var array<string, array<int, string>> */
    protected array $errors = [];
    /** @var array<string, mixed> */
    protected array $data;
    /** @var array<string, string> */
    protected array $customMessages = [];
    /** @var array<string, callable> */
    protected static array $customRules = [];
    protected ?DatabaseManagerInterface $db;

    /** @param array<string, mixed> $data */
    public function __construct(array $data, ?DatabaseManagerInterface $db = null)
    {
        $this->data = $data;
        $this->db = $db;
    }

    protected function getDatabaseManager(): ?DatabaseManagerInterface
    {
        if ($this->db) {
            return $this->db;
        }

        if (function_exists('container')) {
            try {
                return container()->get(DatabaseManagerInterface::class);
            } catch (\Exception $e) { // @codeCoverageIgnoreStart
                return null;
            } // @codeCoverageIgnoreEnd
        }

        return null; // @codeCoverageIgnore
    }

    /** @param array<string, string> $messages */
    public function setCustomMessages(array $messages): void
    {
        $this->customMessages = $messages;
    }

    public static function extend(string $rule, callable $callback): void
    {
        static::$customRules[$rule] = $callback;
    }

    /** @param array<string, string> $rules */
    public function validate(array $rules, array $messages = []): bool
    {
        $this->errors = [];
        $this->customMessages = array_merge($this->customMessages, $messages);

        foreach ($rules as $field => $ruleSet) {
            if ($this->isWildcard($field)) {
                $this->validateWildcard($field, $ruleSet);
            } else {
                $value = $this->getValue($field);
                $fieldRules = explode('|', $ruleSet);

                foreach ($fieldRules as $rule) {
                    $this->applyRule($field, $value, $rule);
                }
            }
        }

        return empty($this->errors);
    }

    protected function isWildcard(string $field): bool
    {
        return strpos($field, '.*.') !== false || strpos($field, '.*') !== false;
    }

    protected function validateWildcard(string $field, string $ruleSet): void
    {
        $parts = explode('.', $field);
        $arrayKey = $parts[0];
        $remainingPath = implode('.', array_slice($parts, 2));

        $array = $this->getValue($arrayKey);

        if (!is_array($array)) {
            return;
        }

        foreach ($array as $index => $item) {
            if ($remainingPath === '') {
                continue;
            }

            $itemData = is_array($item) ? $item : [];
            $itemField = "{$arrayKey}.{$index}.{$remainingPath}";
            $value = $this->getNestedValue($item, $remainingPath);

            $fieldRules = explode('|', $ruleSet);
            foreach ($fieldRules as $rule) {
                $this->applyRule($itemField, $value, $rule);
            }
        }
    }

    protected function getNestedValue(array $data, string $path): mixed
    {
        $parts = explode('.', $path);
        foreach ($parts as $part) {
            if (is_array($data) && isset($data[$part])) {
                $data = $data[$part];
            } else {
                return null;
            }
        }
        return $data;
    }

    protected function getValue(string $field): mixed
    {
        $data = $this->data;
        $parts = explode('.', $field);

        foreach ($parts as $part) {
            if (is_array($data) && isset($data[$part])) {
                $data = $data[$part];
            } else {
                return null;
            }
        }

        return $data;
    }

    protected function applyRule(string $field, mixed $value, string $rule): void
    {
        [$ruleName, $params] = $this->parseRule($rule);

        // Check custom rules first
        if (isset(static::$customRules[$ruleName])) {
            if (!call_user_func(static::$customRules[$ruleName], $field, $value, $params)) {
                $this->addError($field, $ruleName, $params);
            }
            return;
        }

        try {
            switch ($ruleName) {
                case 'required':
                    v::notEmpty()->assert($value);
                    break;
                case 'email':
                    v::email()->assert($value);
                    break;
                case 'max':
                    if ($this->hasFile($field)) {
                        $this->validateFileSize($field, $value, $params, 'max');
                    } elseif (is_numeric($value)) {
                        if ((float) $value > (float) $params) {
                            throw new \Exception("The {$field} must not be greater than {$params}.");
                        }
                    } elseif (is_array($value)) {
                        if (count($value) > (int) $params) {
                            throw new \Exception("The {$field} must not have more than {$params} items.");
                        }
                    } else {
                        v::length(null, (int)$params)->assert($value);
                    }
                    break;
                case 'min':
                    if ($this->hasFile($field)) {
                        $this->validateFileSize($field, $value, $params, 'min');
                    } elseif (is_numeric($value)) {
                        if ((float) $value < (float) $params) {
                            throw new \Exception("The {$field} must be at least {$params}.");
                        }
                    } elseif (is_array($value)) {
                        if (count($value) < (int) $params) {
                            throw new \Exception("The {$field} must have at least {$params} items.");
                        }
                    } else {
                        v::length((int)$params)->assert($value);
                    }
                    break;
                case 'numeric':
                    v::numericVal()->assert($value);
                    break;
                case 'alpha':
                    v::alpha()->assert($value);
                    break;
                case 'alphanumeric':
                    v::alnum()->assert($value);
                    break;
                case 'url':
                    v::url()->assert($value);
                    break;
                case 'ip':
                    v::ip()->assert($value);
                    break;
                case 'in':
                    $values = explode(',', $params);
                    v::in($values)->assert($value);
                    break;
                case 'unique':
                    $this->validateUnique($field, $value, $params);
                    break;
                case 'confirmed':
                    $this->validateConfirmed($field, $value);
                    break;
                case 'date':
                    v::date()->assert($value);
                    break;
                case 'before':
                case 'after':
                    $this->validateDateComparison($field, $value, $ruleName, $params);
                    break;
                case 'file':
                    $this->validateIsFile($value);
                    break;
                case 'mimes':
                    $this->validateMimes($field, $value, $params);
                    break;
                case 'image':
                    $this->validateImage($field, $value);
                    break;
                    // Add more rules as needed
            }
        } catch (\Exception $e) {
            $this->addError($field, $ruleName, $params);
        }
    }

    protected function hasFile(string $field): bool
    {
        $value = $this->getValue($field);
        return $value instanceof \Psr\Http\Message\UploadedFileInterface;
    }

    protected function validateIsFile(mixed $value): void
    {
        if (!($value instanceof \Psr\Http\Message\UploadedFileInterface)) {
            throw new \Exception('Not a file');
        }
    }

    protected function validateFileSize(string $field, mixed $value, string $params, string $operator): void
    {
        $this->validateIsFile($value);
        /** @var \Psr\Http\Message\UploadedFileInterface $value */
        $size = $value->getSize(); // in bytes
        $limit = (int)$params * 1024; // params in KB

        if ($operator === 'max' && $size > $limit) {
            throw new \Exception("File too large.");
        }
        if ($operator === 'min' && $size < $limit) {
            throw new \Exception("File too small.");
        }
    }

    protected function validateMimes(string $field, mixed $value, string $params): void
    {
        $this->validateIsFile($value);
        /** @var \Psr\Http\Message\UploadedFileInterface $value */
        $allowed = explode(',', $params);

        $filename = $value->getClientFilename();
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed)) {
            throw new \Exception("Invalid file type.");
        }
    }

    protected function validateImage(string $field, mixed $value): void
    {
        $this->validateMimes($field, $value, 'jpg,jpeg,png,gif,bmp,svg,webp');
    }

    protected function validateUnique(string $field, mixed $value, string $params): void
    {
        $parts = explode(',', $params);
        $table = $parts[0];
        $column = $parts[1] ?? $field;
        $ignoreId = $parts[2] ?? null;
        $idColumn = $parts[3] ?? 'id';

        $db = $this->getDatabaseManager();
        if (!$db) {
            throw new \RuntimeException("Database manager not available for unique validation."); // @codeCoverageIgnore
        }

        $query = $db->table($table)->where($column, $value);

        if ($ignoreId) {
            $query->where($idColumn, '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new \Exception("Value already exists.");
        }
    }

    protected function validateConfirmed(string $field, mixed $value): void
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->getValue($confirmationField);

        if ($value !== $confirmationValue) {
            throw new \Exception("Confirmation does not match.");
        }
    }

    protected function validateDateComparison(string $field, mixed $value, string $rule, string $target): void
    {
        // Target can be another field or a date string
        $targetValue = $this->getValue($target) ?? $target;

        try {
            $date = new \DateTime($value);
            $targetDate = new \DateTime($targetValue);
        } catch (\Exception $e) {
            throw new \Exception("Invalid date format.");
        }

        if ($rule === 'before' && !($date < $targetDate)) {
            throw new \Exception("Date must be before {$target}.");
        }

        if ($rule === 'after' && !($date > $targetDate)) {
            throw new \Exception("Date must be after {$target}.");
        }
    }

    /** @return array{0: string, 1: string|null} */
    protected function parseRule(string $rule): array
    {
        if (strpos($rule, ':') !== false) {
            return explode(':', $rule, 2);
        }
        return [$rule, null];
    }

    protected function addError(string $field, string $rule, ?string $params = null): void
    {
        $message = $this->customMessages["{$field}.{$rule}"] ?? $this->formatError($field, $rule, $params);
        $this->errors[$field][] = $message;
    }

    protected function formatError(string $field, string $rule, ?string $params = null): string
    {
        $messages = [
            'required' => "The {$field} field is required.",
            'email' => "The {$field} must be a valid email address.",
            'min' => "The {$field} must be at least {$params} characters.",
            'max' => "The {$field} must not exceed {$params} characters.",
            'numeric' => "The {$field} must be numeric.",
            'alpha' => "The {$field} must contain only letters.",
            'alphanumeric' => "The {$field} must contain only letters and numbers.",
            'url' => "The {$field} must be a valid URL.",
            'ip' => "The {$field} must be a valid IP address.",
            'unique' => "The {$field} has already been taken.",
            'confirmed' => "The {$field} confirmation does not match.",
            'date' => "The {$field} is not a valid date.",
            'before' => "The {$field} must be a date before {$params}.",
            'after' => "The {$field} must be a date after {$params}.",
            'file' => "The {$field} must be a file.",
        ];

        return $messages[$rule] ?? "The {$field} field is invalid.";
    }

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /** @return array<string, mixed> */
    public function validated(): array
    {
        if ($this->fails()) {
            throw new \Core\Validation\Internal\ValidationException($this->errors());
        }

        return $this->data;
    }
}
