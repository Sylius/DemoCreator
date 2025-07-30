<?php

declare(strict_types=1);

namespace App\StoreDesigner\Client;

final readonly class GptResponse
{
    public function __construct(private array $raw) {}

    public function toArray(): array
    {
        return $this->raw;
    }

    public function hasFunctionCall(): bool
    {
        return isset($this->raw['function_call']);
    }

    public function getFunctionCallName(): ?string
    {
        return $this->raw['function_call']['name'] ?? null;
    }

    public function getFunctionCallData(): ?array
    {
        if (!isset($this->raw['function_call']['arguments'])) {
            return null;
        }
        $json = $this->raw['function_call']['arguments'];
        $data = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
        // Heurystyka: spróbuj naprawić ucięty JSON (prosta: domknięcie nawiasów)
        $fixed = $this->tryRepairJson($json);
        $data = json_decode($fixed, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
        return null;
    }

    public function getContent(): ?string
    {
        return $this->raw['content'] ?? null;
    }

    public function isTruncated(): bool
    {
        // Heurystyka: finish_reason = 'length' lub niepoprawny JSON w function_call
        if (($this->raw['finish_reason'] ?? null) === 'length') {
            return true;
        }
        if ($this->hasFunctionCall() && $this->getFunctionCallData() === null) {
            return true;
        }
        return false;
    }

    private function tryRepairJson(string $json): string
    {
        // Prosta heurystyka: domknij nawiasy klamrowe
        $open = substr_count($json, '{');
        $close = substr_count($json, '}');
        while ($close < $open) {
            $json .= '}';
            $close++;
        }
        $openArr = substr_count($json, '[');
        $closeArr = substr_count($json, ']');
        while ($closeArr < $openArr) {
            $json .= ']';
            $closeArr++;
        }
        return $json;
    }
} 