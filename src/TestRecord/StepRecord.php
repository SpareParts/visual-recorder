<?php declare(strict_types=1);
namespace SpareParts\VisualRecorder\TestRecord;

class StepRecord
{
	private string $name;
	private ?string $imagePath;

	public function __construct(string $name, ?string $imagePath)
	{
		$this->name = $name;
		$this->imagePath = $imagePath;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getImagePath(): ?string
	{
		return $this->imagePath;
	}
}
