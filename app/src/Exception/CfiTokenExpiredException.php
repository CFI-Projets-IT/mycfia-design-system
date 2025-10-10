<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception levée lorsque le jeton CFI est expiré ou invalide (HTTP 401).
 *
 * Cette exception indique que l'utilisateur doit se reconnecter.
 */
class CfiTokenExpiredException extends CfiApiException
{
    public function __construct(
        string $message = '',
        private readonly ?string $correlationId = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 401, $previous);
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }
}
