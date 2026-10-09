<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use RuntimeException;

/**
 * A QueryException with the data taken out, substituted for the original before anything reports it
 * (logs, Telegram, Mission Control). The original message carries personal data twice: Laravel fills
 * the bindings into the SQL, and PostgreSQL adds its own DETAIL line with the failing values
 * ("Failing row contains (...)", "Key (email)=(...) already exists"). Only the SQLSTATE, the
 * connection, the SQL template with its `?` placeholders and the place of the query are kept.
 * The original is deliberately not chained as `previous`: loggers print the whole chain.
 */
class RedactedQueryException extends RuntimeException
{
    public static function from(QueryException $e): self
    {
        $redacted = new self(sprintf(
            'SQLSTATE[%s] on connection [%s]: %s',
            $e->getCode(),
            $e->getConnectionName(),
            $e->getSql(),
        ));

        $redacted->file = $e->getFile();
        $redacted->line = $e->getLine();

        return $redacted;
    }
}
