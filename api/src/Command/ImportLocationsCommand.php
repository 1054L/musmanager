<?php

namespace App\Command;

use App\Entity\Province;
use App\Entity\Town;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-locations',
    description: 'Imports provinces and towns from JSON files',
)]
class ImportLocationsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $provinciasPath = $this->projectDir . '/../web/src/assets/provincias.json';
        $poblacionesPath = $this->projectDir . '/../web/src/assets/poblaciones.json';

        if (!file_exists($provinciasPath) || !file_exists($poblacionesPath)) {
            $io->error('JSON files not found in web/src/assets/');
            return Command::FAILURE;
        }

        $provincias = json_decode(file_get_content($provinciasPath), true);
        $poblaciones = json_decode(file_get_content($poblacionesPath), true);

        $io->note('Importing provinces...');
        $provinceMap = [];
        foreach ($provincias as $pData) {
            $province = new Province();
            $province->setName($pData['label']);
            $province->setCode($pData['code']);
            $this->entityManager->persist($province);
            $provinceMap[$pData['code']] = $province;
        }
        $this->entityManager->flush();

        $io->note('Importing towns...');
        foreach ($poblaciones as $tData) {
            if (!isset($provinceMap[$tData['parent_code']])) continue;
            
            $town = new Town();
            $town->setName($tData['label']);
            $town->setCode($tData['code']);
            $town->setProvince($provinceMap[$tData['parent_code']]);
            $this->entityManager->persist($town);
        }
        $this->entityManager->flush();

        $io->success('Import completed successfully!');

        return Command::SUCCESS;
    }
}
