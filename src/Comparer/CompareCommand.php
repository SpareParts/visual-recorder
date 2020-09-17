<?php declare(strict_types=1);
namespace SpareParts\VisualRecorder\Comparer;

use SpareParts\VisualRecorder\TestRecord\Result;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompareCommand extends Command
{
	protected static $defaultName = 'compare';

	private Comparer $comparer;

	/**
	 * @param Comparer $comparer
	 */
	public function __construct(Comparer $comparer)
	{
		parent::__construct();
		$this->comparer = $comparer;
	}

	protected function configure()
	{
		$this
			->setDescription("Tool for comparison of VisualRecorder image sets using imagick")
			->addArgument('pathA', InputArgument::REQUIRED, 'Path to result set A (result_.*)')
			->addArgument('pathB', InputArgument::REQUIRED, 'Path to result set B (result_.*)')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
//		$resemblejsPath = $this->decidePath($input);
		$pathA = realpath(getcwd() . '/' . $input->getArgument('pathA'));
		$pathB = realpath(getcwd() . '/' . $input->getArgument('pathB'));

		$output->writeln([
			sprintf('Comparing sets %s and %s.', $pathA, $pathB),
			sprintf('Let\'s rumble!'),
		]);

		$data = file_get_contents($pathA);
		/** @var Result $setA */
		$setA = unserialize($data);

		$data = file_get_contents($pathB);
		/** @var Result $setB */
		$setB = unserialize($data);

		$pairs = []; $stepsB = [];
		foreach ($setB->getSteps() as $step) {
			$stepsB[$step->getName()] = $step;
		}

		foreach ($setA->getSteps() as $step) {
			$pair['a'] = $step;
			if (isset($stepsB[$step->getName()])) {
				$pair['b'] = $stepsB[$step->getName()];
				unset($stepsB[$step->getName()]);
			}
			$pairs[] = $pair;
		}

		foreach ($stepsB as $step) {
			$pairs[] = ['b' => $step];
		}

		foreach ($pairs as $pair) {
			if (isset($pair['a']) && isset($pair['b']))
			$result = $this->comparer->compare($pair['a']->getImagePath(), $pair['b']->getImagePath());
		}


		// potrebujeme seed, pretoze napr. username sa lisi
		$result->getCompareResult()->writeImage('/app/_output/tmp.png');

		return 0;
	}
}
