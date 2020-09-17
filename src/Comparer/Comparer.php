<?php declare(strict_types=1);
namespace SpareParts\VisualRecorder\Comparer;

use Symfony\Component\Process\Process;

class Comparer
{

	public function compare(string $pathA, string $pathB): ComparisonResult
	{
		$imagick1 = new \Imagick($pathA);
		$imagick2 = new \Imagick($pathB);

		$imagick1Size = $imagick1->getImageGeometry();
		$imagick2Size = $imagick2->getImageGeometry();

		$maxWidth = max($imagick1Size['width'], $imagick2Size['width']);
		$maxHeight = max($imagick1Size['height'], $imagick2Size['height']);

		$imagick1->extentImage($maxWidth, $maxHeight, 0, 0);
		$imagick2->extentImage($maxWidth, $maxHeight, 0, 0);

		try {
			$result = $imagick1->compareImages($imagick2, \Imagick::METRIC_MEANSQUAREERROR);
			$result[0]->setImageFormat('png');
//			$result['currentImage'] = clone $imagick2;
//			$result['currentImage']->setImageFormat('png');
		}
		catch (\ImagickException $e) {
			throw new \RuntimeException("Couldn\'t compare image1 $pathA and image2 $pathB.", 0, $e);
		}
		return new ComparisonResult($result[0], $result[1]);
	}
}
