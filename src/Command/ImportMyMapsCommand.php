<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Command;

use Contao\CoreBundle\Framework\ContaoFramework;
use Mandrael\ContaoMaplibreBundle\Service\GoogleMyMapsImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'contao:maplibre:import-mymaps',
    description: 'Importiert die Marker einer "Google My Maps"-Karte (KML) als MapLibre-Standorte.',
)]
final class ImportMyMapsCommand extends Command
{
    public function __construct(
        private readonly GoogleMyMapsImporter $importer,
        private readonly ContaoFramework $framework,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('mid', InputArgument::REQUIRED, 'Die "mid" der Google-My-Maps-Karte (aus der Embed-URL).')
            ->addOption('category', 'c', InputOption::VALUE_REQUIRED, 'Kategorie für die importierten Standorte.', 'Kursorte')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->framework->initialize();

        $io = new SymfonyStyle($input, $output);
        $mid = (string) $input->getArgument('mid');
        $category = (string) $input->getOption('category');

        try {
            $result = $this->importer->import($mid, $category);
        } catch (\Throwable $e) {
            $io->error('Import fehlgeschlagen: '.$e->getMessage());

            return Command::FAILURE;
        }

        if ($result['names']) {
            $io->listing($result['names']);
        }

        $io->success(sprintf(
            '%d Standorte importiert, %d übersprungen (Kategorie "%s").',
            $result['imported'],
            $result['skipped'],
            $category
        ));

        return Command::SUCCESS;
    }
}
