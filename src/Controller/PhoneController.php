<?php

namespace App\Controller;

use App\Entity\Phone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PhoneRepository;
use Symfony\Component\HttpFoundation\Request;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api')]
class PhoneController extends AbstractController
{
    #[Route('/phones', name: 'app_phone', methods: ['GET'])]
    public function getPhones(PhoneRepository $phoneRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache, Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
    
        $idCache = "getPhones_page{$page}_limit{$limit}";
    
        $phones = $cache->get($idCache, function (ItemInterface $item) use ($phoneRepository, $page, $limit) {
            echo("L'élément n'est pas encore en cache !\n");
            $item->tag("phoneCache");
            return $phoneRepository->findAllWithPagination($page, $limit);
        });
    
        $jsonPhones = $serializer->serialize($phones, 'json');
        return new JsonResponse($jsonPhones, Response::HTTP_OK, [], true);
    }

    #[Route('/phone/{id}', name: 'app_details_phone', methods: ['GET'])]
    public function getDetailsPhones(Phone $phone, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getDetailsPhone_" . $phone->getId();
    
        $phoneDetails = $cache->get($idCache, function (ItemInterface $item) use ($phone) {
            echo("L'élément n'est pas encore en cache !\n");
            $item->tag("phoneCache");
            return $phone;
        });
    
        if ($phoneDetails === null) {
            return new JsonResponse(['message' => 'Phone details not found'], Response::HTTP_NOT_FOUND);
        }

        $jsonPhone = $serializer->serialize($phoneDetails, 'json');
        return new JsonResponse($jsonPhone, Response::HTTP_OK, [], true);
    }
}
