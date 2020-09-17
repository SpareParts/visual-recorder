<?php declare(strict_types=1);
namespace SpareParts\VisualRecorder;

use Codeception\Module\WebDriver;

class ScreenshotHandler
{
	private WebDriver $webDriver;

	private array $excludedElements = [];

	public function __construct(WebDriver $webDriver, array $excludedElements)
	{
		$this->webDriver = $webDriver;
		$this->excludedElements = $excludedElements;
	}

	public function takeScreenshot(): \Imagick
	{
		foreach ($this->excludedElements as $element) {
			$this->setVisibility($element, false);
		}
		foreach ($this->excludedElements as $element) {
			$this->webDriver->waitForElementNotVisible($element);
		}

		$image = $this->screenshot();

		foreach ($this->excludedElements as $element) {
			$this->setVisibility($element, true);
		}
		foreach ($this->excludedElements as $element) {
			$this->webDriver->waitForElementVisible($element);
		}

		return $image;
	}

	private function screenshot(): \Imagick
	{
		$screenShotImage = new \Imagick();

//		$height = $this->webDriver->webDriver->executeScript("var ele=document.querySelector('html'); return ele.scrollHeight;");
//		list($viewportHeight, $devicePixelRatio) = $this->webDriver->webDriver->executeScript("return [window.innerHeight, window.devicePixelRatio]");
//
//		$itr = $height / $viewportHeight;
//
//		for ($i = 0; $i < intval($itr); $i++) {
//			$screenshotBinary = $this->webDriver->webDriver->takeScreenshot();
//			$screenShotImage->readimageblob($screenshotBinary);
//			$this->webDriver->webDriver->executeScript("window.scrollBy(0, {$viewportHeight});");
//		}

		$screenshotBinary = $this->webDriver->webDriver->takeScreenshot();
		$screenShotImage->readimageblob($screenshotBinary);
//		$heightOffset = $viewportHeight - ($height - (intval($itr) * $viewportHeight));
//		$screenShotImage->cropImage(0, 0, 0, $heightOffset * $devicePixelRatio);

		$screenShotImage->resetIterator();
		return $screenShotImage;
	}

	private function setVisibility($elementSelector, $isVisible)
	{
		$styleVisibility = $isVisible ? 'visible' : 'hidden';
		$this->webDriver->webDriver->executeScript('
            var elements = [];
            elements = document.querySelectorAll("' . $elementSelector . '");
            if( elements.length > 0 ) {
                for (var i = 0; i < elements.length; i++) {
                    elements[i].style.visibility = "' . $styleVisibility . '";
                }
            }
        ');
	}

	private function getCoordinates(?string $elementId = null): array
	{
		if (is_null($elementId)) {
			$elementId = 'body';
		}

		$elementExists = (bool)$this->webDriver->webDriver->executeScript('return document.querySelectorAll( "' . $elementId . '" ).length > 0;');

		if (!$elementExists) {
			throw new \Exception("The element you want to examine ('" . $elementId . "') was not found.");
		}

		$imageCoords = $this->webDriver->webDriver->executeScript('
              var rect = document.querySelector( "' . $elementId . '" ).getBoundingClientRect();
              return {"offset_x": rect.left, "offset_y": rect.top, "width": rect.width, "height": rect.height};
        ');

		return $imageCoords;
	}
}
