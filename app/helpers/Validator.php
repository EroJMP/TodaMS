<?php

class Validator
{
    public static function required(array $input, array $fields): array
    {
        $errors = [];

        foreach ($fields as $field) {
            if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        return $errors;
    }
}
