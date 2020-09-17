<?php declare(strict_types=1);
namespace SpareParts\VisualRecorder\TestRecord;

class Result
{
	private array $steps = [];
	private string $name;
	private string $baseDirectory;

	public function __construct(string $name, string $baseDirectory)
	{
		$this->name = $name;
		$this->baseDirectory = $baseDirectory;
	}

	public function addStep(StepRecord $stepRecord): void
	{
		$this->steps[] = $stepRecord;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getBaseDirectory(): string
	{
		return $this->baseDirectory;
	}

	/**
	 * @return array|StepRecord[]
	 */
	public function getSteps(): array
	{
		return $this->steps;
	}
}
