<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Exceptions;

// No specific additions needed for this one, it inherits all from SiaMessageException.
// Its type alone signifies the nature of the error.
class InvalidFrameException extends SiaMessageException {}
