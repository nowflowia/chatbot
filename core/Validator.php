<?php

namespace Core;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors   = [];
    private array $validated = [];

    public function __construct(array $data, array $rules)
    {
        $this->data  = $data;
        $this->rules = $rules;
        $this->validate();
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $param] = explode(':', $rule, 2);
                    $params = explode(',', $param);
                }

                $method = 'rule' . ucfirst($rule);
                if (method_exists($this, $method)) {
                    $error = $this->$method($field, $value, $params);
                    if ($error !== null) {
                        $this->errors[$field][] = $error;
                        break;
                    }
                }
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    public function validated(): array
    {
        return $this->validated;
    }

    // Rules
    private function ruleRequired(string $field, mixed $value, array $params): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return "O campo {$field} é obrigatório.";
        }
        return null;
    }

    private function ruleEmail(string $field, mixed $value, array $params): ?string
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "O campo {$field} deve ser um e-mail válido.";
        }
        return null;
    }

    private function ruleMin(string $field, mixed $value, array $params): ?string
    {
        $min = (int)($params[0] ?? 0);
        if ($value !== null && $value !== '' && strlen((string)$value) < $min) {
            return "O campo {$field} deve ter no mínimo {$min} caracteres.";
        }
        return null;
    }

    private function ruleMax(string $field, mixed $value, array $params): ?string
    {
        $max = (int)($params[0] ?? 255);
        if ($value !== null && $value !== '' && strlen((string)$value) > $max) {
            return "O campo {$field} deve ter no máximo {$max} caracteres.";
        }
        return null;
    }

    private function ruleConfirmed(string $field, mixed $value, array $params): ?string
    {
        $confirmation = $this->data[$field . '_confirmation'] ?? null;
        if ($value !== $confirmation) {
            return "O campo {$field} não confere com a confirmação.";
        }
        return null;
    }

    private function ruleIn(string $field, mixed $value, array $params): ?string
    {
        if ($value !== null && $value !== '' && !in_array($value, $params, true)) {
            return "O valor do campo {$field} é inválido.";
        }
        return null;
    }

    private function ruleNumeric(string $field, mixed $value, array $params): ?string
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            return "O campo {$field} deve ser numérico.";
        }
        return null;
    }

    private function ruleInteger(string $field, mixed $value, array $params): ?string
    {
        if ($value !== null && $value !== '' && !ctype_digit((string)$value)) {
            return "O campo {$field} deve ser um número inteiro.";
        }
        return null;
    }

    private function ruleUnique(string $field, mixed $value, array $params): ?string
    {
        if ($value === null || $value === '') return null;

        $table     = $params[0] ?? '';
        $column    = $params[1] ?? $field;
        $exceptId  = $params[2] ?? null;

        if (!$table) return null;

        $db  = Database::getInstance();
        $sql = "SELECT COUNT(*) as cnt FROM `{$table}` WHERE `{$column}` = ?";
        $bindings = [$value];

        if ($exceptId) {
            $sql .= " AND id != ?";
            $bindings[] = $exceptId;
        }

        $result = $db->selectOne($sql, $bindings);
        if ((int)($result['cnt'] ?? 0) > 0) {
            return "O campo {$field} já está em uso.";
        }
        return null;
    }

    private function ruleNullable(string $field, mixed $value, array $params): ?string
    {
        return null;
    }

    private function ruleSometimes(string $field, mixed $value, array $params): ?string
    {
        return null;
    }
}
