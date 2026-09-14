<?php

declare(strict_types=1);

namespace NhanAZ\Calculator;

use InvalidArgumentException;
use muqsit\arithmexp\Parser;
use Throwable;

final class ExpressionCalculator {

	/**
	 * These names are provided by arithmexp and must not be requested as variables.
	 * Function calls are additionally detected by extractVariables().
	 */
	private const RESERVED_IDENTIFIERS = [
		"e",
		"euler",
		"false",
		"inf",
		"log2e",
		"log10e",
		"ln2",
		"ln10",
		"lnpi",
		"pi",
		"nan",
		"pi2",
		"pi4",
		"m_1pi",
		"m_2pi",
		"m_2sqrtpi",
		"sqrtpi",
		"sqrt2",
		"sqrt3",
		"sqrt12",
		"true",
		"and",
		"or",
		"xor",
	];

	private ?Parser $parser = null;

	/**
	 * @param list<string> $args
	 */
	public function createRequest(array $args): CalculatorRequest {
		$expressionParts = [];
		$rawValues = [];
		$count = count($args);

		for ($i = 0; $i < $count; ++$i) {
			$arg = (string) $args[$i];
			if (!str_starts_with($arg, "--")) {
				$expressionParts[] = $arg;
				continue;
			}

			$option = substr($arg, 2);
			$equalsPosition = strpos($option, "=");
			if ($equalsPosition === false) {
				$name = $option;
				if (!isset($args[$i + 1]) || str_starts_with((string) $args[$i + 1], "--")) {
					throw new InvalidArgumentException("Option --{$name} needs a value, for example --{$name}=3.");
				}
				$value = (string) $args[++$i];
			} else {
				$name = substr($option, 0, $equalsPosition);
				$value = substr($option, $equalsPosition + 1);
				if ($value === "") {
					throw new InvalidArgumentException("Option --{$name} cannot have an empty value.");
				}
			}

			if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
				throw new InvalidArgumentException("Invalid variable name '{$name}'.");
			}
			if ($this->isReservedIdentifier($name)) {
				throw new InvalidArgumentException("'{$name}' is reserved by the calculator and cannot be assigned.");
			}
			if (array_key_exists($name, $rawValues)) {
				throw new InvalidArgumentException("Variable '{$name}' was assigned more than once.");
			}
			$rawValues[$name] = $value;
		}

		$expression = trim(implode(" ", $expressionParts));
		if ($expression === "") {
			throw new InvalidArgumentException("Missing mathematical expression.");
		}

		return new CalculatorRequest($expression, $rawValues);
	}

	public function parse(string $expression): object {
		return $this->getParser()->parse($expression);
	}

	/** @param array<string, int|float|bool> $values */
	public function evaluate(object $expression, array $values = []): mixed {
		return $expression->evaluate($values);
	}

	/** @param array<string, int|float|bool> $values */
	public function evaluateValue(string $valueExpression, array $values = []): int|float|bool {
		$value = $this->evaluate($this->parse($valueExpression), $values);
		if (!is_int($value) && !is_float($value) && !is_bool($value)) {
			throw new InvalidArgumentException("The value must evaluate to a number or boolean.");
		}
		return $value;
	}

	/**
	 * @param array<string, string> $rawValues
	 * @return array<string, int|float|bool>
	 */
	public function resolveProvidedValues(array $rawValues): array {
		$values = [];
		$errors = [];

		while (count($rawValues) > 0) {
			$resolvedSomething = false;
			foreach ($rawValues as $name => $valueExpression) {
				try {
					$values[$name] = $this->evaluateValue($valueExpression, $values);
					unset($rawValues[$name], $errors[$name]);
					$resolvedSomething = true;
				} catch (Throwable $e) {
					$errors[$name] = $e;
				}
			}

			if (!$resolvedSomething) {
				$name = array_key_first($rawValues);
				throw $errors[$name] ?? new InvalidArgumentException("Unable to resolve variable '{$name}'.");
			}
		}

		return $values;
	}

	/** @return list<string> */
	public function extractVariables(string $expression): array {
		preg_match_all('/(?<![A-Za-z0-9_])[A-Za-z_][A-Za-z0-9_]*/', $expression, $matches, PREG_OFFSET_CAPTURE);
		$variables = [];

		foreach ($matches[0] as [$name, $offset]) {
			$name = (string) $name;
			if ($this->isReservedIdentifier($name)) {
				continue;
			}

			$remainingExpression = substr($expression, (int) $offset + strlen($name));
			if (preg_match('/^\s*\(/', $remainingExpression) === 1) {
				continue;
			}
			if (!in_array($name, $variables, true)) {
				$variables[] = $name;
			}
		}

		return $variables;
	}

	private function getParser(): Parser {
		return $this->parser ??= Parser::createDefault();
	}

	private function isReservedIdentifier(string $name): bool {
		return in_array(strtolower($name), self::RESERVED_IDENTIFIERS, true);
	}
}
