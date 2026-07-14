<?php

declare(strict_types=1);

namespace App\Command;

use App\Loader\EventSourceInterface;
use App\Loader\SourceStateManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
#[AsCommand(
    name: 'app:status',
    description: 'Shows the current event fetching status per source',
)]
class StatusCommand extends Command
{
    /** @var EventSourceInterface[] */
    private array $sources;

    public function __construct(
        iterable $sources,
        private SourceStateManagerInterface $stateManager
    ) {
        $this->sources = is_array($sources) ? $sources : iterator_to_array($sources);
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (empty($this->sources)) {
            $output->writeln("No event sources configured.");
            return Command::FAILURE;
        }

        $data = [];
        foreach ($this->sources as $source) {
            $name = $source->getName();
            $caught = $this->stateManager->getLastEventId($name);
            $max = $source->getMaxEvents();
            
            $progress = 'N/A';
            if ($max !== null && $max > 0) {
                $percentage = round(($caught / $max) * 100, 2);
                $progress = $percentage . '%';
            }

            $data[] = [
                'Source Name' => $name,
                'Events Caught' => $caught,
                'Max Events' => $max ?? 'Unknown',
                'Progress' => $progress,
            ];
        }

        foreach ($data as $row) {
            $output->writeln(sprintf('%s: %s / %s (%s)', $row['Source Name'], $row['Events Caught'], $row['Max Events'], $row['Progress']));
        }

        return Command::SUCCESS;
    }
}
