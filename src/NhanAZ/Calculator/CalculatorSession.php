<?php

declare(strict_types=1);

namespace NhanAZ\Calculator;

use pocketmine\player\Player;

final class CalculatorSession {

	private int $variableIndex = 0;

	/**
	 * @param list<string> $expressionVariables
	 * @param list<string> $missingVariables
	 * @param array<string, int|float|bool> $values
	 */
	public function __construct(
		private readonly Player $player,
		private readonly object $expression,
		private readonly array $expressionVariables,
		private readonly array $missingVariables,
		private array $values,
	) {}

	public function getPlayer(): Player {
		return $this->player;
	}

	public function getExpression(): object {
		return $this->expression;
	}

	/** @return list<string> */
	public function getExpressionVariables(): array {
		return $this->expressionVariables;
	}

	/** @return array<string, int|float|bool> */
	public function getValues(): array {
		return $this->values;
	}

	public function getCurrentVariable(): string {
		return $this->missingVariables[$this->variableIndex];
	}

	public function setCurrentValue(int|float|bool $value): void {
		$this->values[$this->getCurrentVariable()] = $value;
		++$this->variableIndex;
	}

	public function isComplete(): bool {
		return $this->variableIndex >= count($this->missingVariables);
	}
}
