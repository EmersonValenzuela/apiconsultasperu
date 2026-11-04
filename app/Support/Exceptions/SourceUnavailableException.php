<?php

namespace App\Support\Exceptions;

use RuntimeException;

/**
 * La fuente externa no responde y no hay dato guardado para servir (HTTP 503).
 */
class SourceUnavailableException extends RuntimeException
{
}
