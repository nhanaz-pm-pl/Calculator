<?php

declare(strict_types=1);

namespace NhanAZ\Calculator;

final class CalculatorRequest {

	/**
	 * @param array<string, string> $rawValues
	 */
	public function __construct(
		public readonly string $expression,
		public readonly array $rawValues,
	) {}
}
