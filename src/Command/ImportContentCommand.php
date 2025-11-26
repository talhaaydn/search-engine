<?php

namespace App\Command;

use App\DTO\ContentDTO;
use App\Entity\Content;
use App\Repository\ContentRepository;
use App\Service\Provider\ContentScorer;
use App\Service\Provider\Manager\ProviderManager;
use Psr\Log\LoggerInterface;
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
        private readonly ProviderManager $providerManager,
        private readonly ContentScorer $contentScorer,
        private readonly ContentRepository $contentRepository,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Tüm provider\'lardan içerik import ediliyor...');

        // 1. Provider'lardan içerikleri topla
        $dtos = $this->providerManager->importAll();

        if (empty($dtos)) {
            $io->warning('Hiçbir provider\'dan içerik alınamadı');
            return Command::SUCCESS;
        }

        $io->text(sprintf('%d içerik toplandı', count($dtos)));

        $contents = $this->convertDtosToEntities($dtos, $io);

        if (empty($contents)) {
            $io->error('Hiçbir içerik işlenemedi');
            return Command::FAILURE;
        }

        $io->text(sprintf('%d içerik işlendi', count($contents)));

        try {
            $this->contentRepository->upsertContents($contents);
            
            $this->logger->info('Contents saved to database', [
                'total_count' => count($contents)
            ]);

            $io->success(sprintf('%d içerik başarıyla veritabanına kaydedildi', count($contents)));

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->logger->error('Failed to save contents to database', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $io->error('Veritabanına kayıt sırasında hata oluştu: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * @param ContentDTO[] $dtos
     * @return Content[]
     */
    private function convertDtosToEntities(array $dtos, SymfonyStyle $io): array
    {
        $contents = [];
        $failedCount = 0;

        $io->progressStart(count($dtos));

        foreach ($dtos as $dto) {
            try {
                $score = $this->contentScorer->calculateScore($dto);

                $content = new Content();
                $content->setProviderName($dto->provider)
                    ->setProviderContentId($dto->contentId)
                    ->setTitle($dto->title)
                    ->setContentType($dto->type)
                    ->setScore($score);

                $contents[] = $content;

            } catch (\Throwable $e) {
                $failedCount++;
                
                $this->logger->error('Failed to process content', [
                    'provider' => $dto->provider,
                    'content_id' => $dto->contentId,
                    'error' => $e->getMessage(),
                ]);
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        if ($failedCount > 0) {
            $io->warning(sprintf('%d içerik işlenemedi', $failedCount));
        }

        return $contents;
    }
}

