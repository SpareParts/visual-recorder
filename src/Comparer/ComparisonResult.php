<?php declare(strict_types=1);
namespace SpareParts\VisualRecorder\Comparer;

class ComparisonResult
{
	private \Imagick $compareResult;
	private float $similarityIndex;

	public function __construct(\Imagick $compareResult, float $similarityIndex)
	{
		$this->compareResult = $compareResult;
		$this->similarityIndex = $similarityIndex;
	}

	public function getCompareResult(): \Imagick
	{
		return $this->compareResult;
	}

	public function getSimilarityIndex(): float
	{
		return $this->similarityIndex;
	}
}
