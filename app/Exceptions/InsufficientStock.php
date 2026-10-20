<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Stock would drop below zero. Callers check stock first; this is the last line of defence.
 */
final class InsufficientStock extends RuntimeException {}
