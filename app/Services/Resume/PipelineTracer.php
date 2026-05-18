<?php

namespace App\Services\Resume;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Correlation-aware structured logging for resume pipeline stages.
 */
class PipelineTracer
{
    private ?string $correlationId = null;

    /**
     * @param  array<string, mixed>  $context
     */
    public function start(string $operation, array $context = []): string
    {
        $this->correlationId = (string) Str::uuid();

        Log::info('[PipelineTracer] START', array_merge($context, [
            'operation' => $operation,
            'correlation_id' => $this->correlationId,
        ]));

        return $this->correlationId;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function log(string $event, array $context = []): void
    {
        Log::info('[PipelineTracer] ' . $event, array_merge($context, [
            'correlation_id' => $this->correlationId,
        ]));
    }

    public function correlationId(): ?string
    {
        return $this->correlationId;
    }

    public function headers(): array
    {
        if ($this->correlationId === null) {
            return [];
        }

        return ['X-Correlation-Id' => $this->correlationId];
    }
}
