<?php

namespace App\Command;

use App\Service\Provider\Manager\ProviderManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-content',
    description: 'Tüm provider\'lardan içerik verilerini import eder',
)]
class ImportContentCommand extends Command
{
    public function __construct(
        private ProviderManager $providerManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Tüm provider\'lardan içerik import ediliyor...');

        $contents = $this->providerManager->importAll();

        $io->success(sprintf('%d içerik başarıyla import edildi', count($contents)));

        return Command::SUCCESS;
    }
}

