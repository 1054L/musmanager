<?php

namespace App\Controller;

use App\Repository\ProvinceRepository;
use App\Repository\TownRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/location')]
class LocationController extends AbstractController
{
    #[Route('/all', name: 'app_location_all', methods: ['GET'])]
    public function getAll(ProvinceRepository $provinceRepository, TownRepository $townRepository): JsonResponse
    {
        $provinces = $provinceRepository->findAll();
        $towns = $townRepository->findAll();

        $provinceData = array_map(function($p) {
            return [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'code' => $p->getCode()
            ];
        }, $provinces);

        $townData = array_map(function($t) {
            return [
                'id' => $t->getId(),
                'name' => $t->getName(),
                'code' => $t->getCode(),
                'provinceId' => $t->getProvince()->getId()
            ];
        }, $towns);

        return new JsonResponse([
            'provinces' => $provinceData,
            'towns' => $townData
        ]);
    }
}
