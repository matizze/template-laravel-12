<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Base class for domain/business-rule errors.
 *
 * Subclasses should set a sensible HTTP status (default 422) and a stable
 * semantic identifier (e.g. "tenant.not_operable") consumed by clients.
 *
 * When the request expects JSON, the exception renders as the uniform error
 * envelope: {"message": "...", "code": "..."}. For other contexts, the
 * default Laravel exception handler takes over (Whoops in dev, etc.).
 */
abstract class DomainException extends RuntimeException
{
    public function __construct(
        string $message,
        protected readonly string $domainCode = '',
        protected readonly int $status = 422,
    ) {
        parent::__construct($message);
    }

    public function getDomainCode(): string
    {
        return $this->domainCode;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function render(Request $request): ?JsonResponse
    {
        if (! $request->expectsJson()) {
            return null;
        }

        $payload = ['message' => $this->getMessage()];

        if ($this->domainCode !== '') {
            $payload['code'] = $this->domainCode;
        }

        return new JsonResponse($payload, $this->status);
    }
}
