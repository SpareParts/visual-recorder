<?php declare(strict_types=1);
namespace SpareParts\VisualRecorder;

use Codeception\Event\StepEvent;
use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use Codeception\Lib\Interfaces\ScreenshotSaver;
use Codeception\Module\WebDriver;
use Codeception\Step\Comment;
use SpareParts\VisualRecorder\TestRecord\Result;
use SpareParts\VisualRecorder\TestRecord\StepRecord;

class VisualRecorder extends Extension
{
    protected $config = [
        'webdriver_module' => WebDriver::class,
        'output_dir' => 'recorder', // directory inside the `base_path`
        'ignore_elements' => [],
		'ignore_steps' => [],
        'base_path' => null, // default = current output dir
		'force_cleanup' => false,
    ];

    private string $workingBaseDir;
    private string $workingTestDir;

    private ?Result $suiteResult;

    private WebDriver $webDriver;
    private ScreenshotHandler $screenshotHandler;

    public static function getSubscribedEvents()
    {
        return [
			Events::SUITE_INIT => 'receiveModuleContainer',
            Events::SUITE_BEFORE => 'suiteBefore',
			Events::SUITE_AFTER => 'suiteAfter',
			Events::TEST_ERROR => 'suiteAfter',
			Events::TEST_FAIL => 'suiteAfter',
            Events::TEST_BEFORE => 'testBefore',
            Events::STEP_AFTER => 'stepAfter',
        ];
    }

    public function _initialize(): void
    {
		$this->suiteResult = null;
    }

    public function suiteBefore(SuiteEvent $suiteEvent): void
    {
		$this->config['base_path'] ??= \Codeception\Configuration::outputDir();

		$workingBaseDir = $this->config['base_path'] . $this->config['output_dir'];
		$this->readyDirectory($workingBaseDir);
		$this->workingBaseDir = realpath($workingBaseDir) . '/';


		$webDriverModule = $this->getModule($this->config['webdriver_module']);
		if (!$webDriverModule instanceof ScreenshotSaver) {
			throw new \InvalidArgumentException(sprintf(
				'Module with name `%s` (%s) can\'t take screenshots - it should be %s.',
				$this->config['screenshot_module'],
				get_class($this->webDriver),
				WebDriver::class
			));
		}
		$this->webDriver = $webDriverModule;

		$this->screenshotHandler = new ScreenshotHandler(
			$webDriverModule,
			$this->config['ignore_elements'],
		);
    }

	public function suiteAfter(): void
	{
		if ($this->suiteResult) {
			file_put_contents($this->workingBaseDir . 'result_'.base64_encode($this->suiteResult->getName()), serialize($this->suiteResult));
		}
    }

    public function testBefore(TestEvent $testEvent): void
    {
        $test = $testEvent->getTest();
        $this->workingTestDir = $this->workingBaseDir . md5((string)(time().rand(0, 10000))) . '/';

		if (is_dir($this->workingTestDir)) {
			if (!$this->config['force_cleanup']) {
				throw new \RuntimeException(sprintf(
					'Multiple conflicting tests/runs detected! Directory `%s` for test `%s` already exists. You can use config option `force_cleanup: true` to override this conflict.',
					$this->workingTestDir,
					$test->getMetadata()->getName()
				));
			}
			$this->deleteDirectory($this->workingTestDir);
		}

		$this->suiteResult = new Result((string)$test->getMetadata()->getName(), $this->workingTestDir);

		$this->readyDirectory($this->workingTestDir);
//        file_put_contents($this->workingTestDir . 'info.txt', [
//            'Test name: ' . $test->getMetadata()->getName() . "\r\n",
//            'File name: ' . $test->getMetadata()->getFilename(). "\r\n",
//        ], FILE_APPEND);
    }

    public function stepAfter(StepEvent $stepEvent): void
    {
        $step = $stepEvent->getStep();

        if ($step instanceof Comment) {
        	$this->writeln('Recorder: skipping Comment step.');
        	return;
		}

        if ($this->isStepIgnored($stepEvent)) {
			$this->writeln('Recorder: skipping ignored step.');
			return;
		}

        $filename = $this->workingTestDir . md5((string)$step) . '.png';

        $screenshot = $this->screenshotHandler->takeScreenshot();
        $screenshot->writeImage($filename);

		if ($this->suiteResult) {
			$this->suiteResult->addStep(
				new StepRecord((string)$step->getName(), $filename)
			);
		}

//        file_put_contents($this->workingTestDir . 'info.txt', [
//            (string)$step.' :: '.md5((string)$step) . "\r\n",
//        ], FILE_APPEND);
		$this->writeln('Recorder: saving screenshot to '.$filename);
    }

    private function readyDirectory(string $dirName): void
    {
        if (!is_dir($dirName)) {
            if (!mkdir($dirName, 0777, true)) {
                throw new \RuntimeException(sprintf('Unable to create directory `%s`', $dirName));
            }
        }
    }

	private function deleteDirectory(string $directory): void
	{
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($files as $fileinfo) {
			$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
			$todo($fileinfo->getRealPath());
		}
	}

	private function isStepIgnored(StepEvent $e): bool
	{
		$configIgnoredSteps = $this->config['ignore_steps'];
		$annotationIgnoredSteps = $e->getTest()->getMetadata()->getParam('skipRecording');

		$ignoredSteps = array_unique(
			array_merge(
				$configIgnoredSteps,
				is_array($annotationIgnoredSteps) ? $annotationIgnoredSteps : []
			)
		);

		foreach ($ignoredSteps as $stepPattern) {
			$stepRegexp = '/^' . str_replace('*', '.*?', $stepPattern) . '$/i';

			if (preg_match($stepRegexp, $e->getStep()->getAction())) {
				return true;
			}

			if ($e->getStep()->getMetaStep() !== null &&
				preg_match($stepRegexp, $e->getStep()->getMetaStep()->getAction())
			) {
				return true;
			}
		}

		return false;
	}
}
