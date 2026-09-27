<?php

declare(strict_types=1);

namespace Faraid\Exception;

/**
 * The engine reached a state it cannot defend — shares that do not sum to
 * unity, an impossible denominator, an unreachable branch. Always a bug in
 * the engine, never bad input, and always loud.
 */
final class CalculationError extends \RuntimeException
{
}
