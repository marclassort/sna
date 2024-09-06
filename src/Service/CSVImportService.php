<?php

namespace App\Service;

use App\Entity\Member;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CSVImportService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function importMembersFromCSV(UploadedFile $csvFile, Order $order): int
    {
        $handle = fopen($csvFile->getPathname(), 'r');
        $memberCount = 0;

        $isFirstLine = true;

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($isFirstLine) {
                $isFirstLine = false;
                continue;
            }

            if (empty(array_filter($data))) {
                continue;
            }

            $member = new Member();
            $member->setFirstName($data[0]);
            $member->setLastName($data[1]);
            $member->setSex($data[2]);
            $member->setBirthDate($data[3]);
            $member->setAddress($data[4]);
            $member->setPostalCode($data[5]);
            $member->setCity($data[6]);
            $member->setCommande($order);

            $this->entityManager->persist($member);
            $memberCount++;
        }

        fclose($handle);

        $this->entityManager->flush();

        return $memberCount;
    }

    public function importMembersFromXLSX(UploadedFile $xlsxFile, Order $order): int
    {
        $spreadsheet = IOFactory::load($xlsxFile->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $memberCount = 0;

        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            // Skip the header row
            if ($rowIndex == 1) {
                continue;
            }

            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $data = [];
            foreach ($cellIterator as $cell) {
                $data[] = $cell->getValue();
            }

            // Skip empty or invalid rows
            if (empty(array_filter($data))) {
                continue;
            }

            $member = new Member();
            $member->setFirstName($data[0]);
            $member->setLastName($data[1]);
            $member->setSex($data[2]);
            $member->setBirthDate($data[3]);
            $member->setAddress($data[4]);
            $member->setPostalCode($data[5]);
            $member->setCity($data[6]);
            $member->setCommande($order);

            $this->entityManager->persist($member);
            $memberCount++;
        }

        $this->entityManager->flush();

        return $memberCount;
    }
}
