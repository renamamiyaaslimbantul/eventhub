<?php

declare(strict_types=1);

namespace EventHub\Helpers;

/**
 * Validator — rules: required, nullable, email, min:N, max:N,
 * enum:value1,value2, date:Y-m-d, url, numeric, integer.
 *
 * @param array $data        associative [field => value]
 * @param array $ruleMap    associative [field => 'rule1|rule2' | [rule1, rule2]]
 * @return array|null       errors associative atau null jika valid
 */
final class Validator
{
    public static function validate(array $data, array $ruleMap): ?array
    {
        $errors = [];

        foreach ($ruleMap as $field => $rules) {
            $value    = $data[$field] ?? null;
            $rulesStr = is_string($rules) ? $rules : implode('|', $rules);

            foreach (explode('|', $rulesStr) as $rule) {
                if ($rule === '') continue;

                // required
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $errors[$field] = "The {$field} field is required.";
                    break;
                }
                if ($rule === 'nullable') continue;
                if ($value === null || $value === '') continue;

                // email
                if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "The {$field} must be a valid email address.";
                    break;
                }

                // min:N
                if (str_starts_with($rule, 'min:')) {
                    $min = (int)substr($rule, 4);
                    if (strlen((string)$value) < $min) {
                        $errors[$field] = "The {$field} must be at least {$min} characters.";
                        break;
                    }
                }

                // max:N
                if (str_starts_with($rule, 'max:')) {
                    $max = (int)substr($rule, 4);
                    if (strlen((string)$value) > $max) {
                        $errors[$field] = "The {$field} must not exceed {$max} characters.";
                        break;
                    }
                }

                // enum:a,b,c
                if (str_starts_with($rule, 'enum:')) {
                    $allowed = explode(',', substr($rule, 5));
                    if (!in_array($value, $allowed, true)) {
                        $errors[$field] = "The {$field} must be one of: " . implode(', ', $allowed) . ".";
                        break;
                    }
                }

                // date:Y-m-d
                if (str_starts_with($rule, 'date')) {
                    $format = str_contains($rule, ':') ? substr($rule, 5) : 'Y-m-d';
                    $d = \DateTime::createFromFormat($format, $value);
                    if (!($d && $d->format($format) === $value)) {
                        $errors[$field] = "The {$field} must be a valid date ({$format}).";
                        break;
                    }
                }

                // url
                if ($rule === 'url' && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[$field] = "The {$field} must be a valid URL.";
                    break;
                }

                // numeric
                if ($rule === 'numeric' && !is_numeric($value)) {
                    $errors[$field] = "The {$field} must be a number.";
                    break;
                }

                // integer
                if ($rule === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $errors[$field] = "The {$field} must be an integer.";
                    break;
                }
            }
        }

        return $errors === [] ? null : $errors;
    }
}
