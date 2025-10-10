<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception levée lorsque l'accès à une ressource CFI est refusé (HTTP 403).
 *
 * Cette exception indique que l'utilisateur n'a pas les droits nécessaires.
 */
class CfiAccessDeniedException extends CfiApiException
{
    public function __construct(
        string $message = '',
        private readonly ?string $correlationId = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 403, $previous);
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }
}
