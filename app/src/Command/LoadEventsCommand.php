<?php

declare(strict_types=1);

namespace App\Command;

use App\Loader\EventSourceInterface;
use App\Message\ProcessSourceMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:load-events',
    description: 'Initializes event loading by dispatching the first messages',
)]
class LoadEventsCommand extends Command
{
    /** @var EventSourceInterface[] */
    private array $sources;

    public function __construct(
        #[TaggedIterator('app.event_source')] iterable $sources,
        private MessageBusInterface $messageBus
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

        $output->writeln("Dispatching initial messages for event sources...");

        foreach ($this->sources as $source) {
            $this->messageBus->dispatch(new ProcessSourceMessage($source->getName()));
            $output->writeln(sprintf('Dispatched message for source: %s', $source->getName()));
        }

        $output->writeln("All initial messages dispatched! Run workers using: php bin/console messenger:consume async");

        return Command::SUCCESS;
    }
}
