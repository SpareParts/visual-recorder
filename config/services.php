<?php

use SpareParts\VisualRecorder\Comparer\CompareCommand;
use SpareParts\VisualRecorder\Comparer\Comparer;
use Symfony\Component\Console\Application;

return [
	'version' => '0.1b',

	Comparer::class => \DI\create(),


	'commands' => [
		\DI\create(CompareCommand::class)
			->constructor(\DI\get(Comparer::class))
	],
	Application::class => \DI\create()
		->constructor('comparer', \DI\value('version'))
		->method('addCommands', \DI\get('commands')),
];
