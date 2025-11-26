<?php

namespace App\Command;

use App\Entity\Content;
use App\Service\Elasticsearch\ContentIndexer;
use App\Service\Elasticsearch\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:index-content-elasticsearch',
    description: 'Index all content to Elasticsearch'
)]
class IndexContentToElasticsearchCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ElasticsearchService $elasticsearchService,
        private readonly ContentIndexer $contentIndexer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('recreate', null, InputOption::VALUE_NONE, 'Recreate index');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->elasticsearchService->isHealthy()) {
            $io->error('Elasticsearch is not healthy.');
            return Command::FAILURE;
        }

        if ($input->getOption('recreate')) {
            $this->elasticsearchService->deleteIndex();
        }

        $this->elasticsearchService->createIndex();

        $contents = $this->entityManager->getRepository(Content::class)->findAll();
        
        if (empty($contents)) {
            $io->warning('No content found.');
            return Command::SUCCESS;
        }

        $this->contentIndexer->bulkIndexContents($contents);

        $io->success(sprintf('Indexed %d contents.', count($contents)));
        return Command::SUCCESS;
    }
}

