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
        // Récupérer les paramètres de pagination de la requête
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
    
        // Générer une clé de cache unique basée sur les paramètres de la requête
        $idCache = "getPhones_page{$page}_limit{$limit}";
    
        // Récupérer les téléphones depuis le cache, s'ils existent. Sinon, récupérer depuis la base de données et mettre en cache.
        $phones = $cache->get($idCache, function (ItemInterface $item) use ($phoneRepository, $page, $limit) {
            echo("L'élément n'est pas encore en cache !\n");
            $item->tag("phoneCache");
            return $phoneRepository->findAllWithPagination($page, $limit);
        });
    
        // Sérialiser les données des téléphones au format JSON
        $jsonPhones = $serializer->serialize($phones, 'json');
        // Retourner les données des téléphones sous forme de réponse JSON
        return new JsonResponse($jsonPhones, Response::HTTP_OK, [], true);
    }

    #[Route('/phone/{id}', name: 'app_details_phone', methods: ['GET'])]
    public function getDetailsPhones(Phone $phone, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        // Générer une clé de cache unique basée sur l'identifiant du téléphone
        $idCache = "getDetailsPhone_" . $phone->getId();
    
        // Récupérer les détails du téléphone depuis le cache s'ils existent, sinon récupérer depuis la base de données et mettre en cache
        $phoneDetails = $cache->get($idCache, function (ItemInterface $item) use ($phone) {
            echo("L'élément n'est pas encore en cache !\n");
            $item->tag("phoneCache");
            return $phone;
        });
    
        // Vérifier si les détails du téléphone ont été trouvés
        if ($phoneDetails === null) {
            // Retourner une réponse JSON avec un message d'erreur si les détails du téléphone n'ont pas été trouvés
            return new JsonResponse(['message' => 'Détails du produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Sérialiser les détails du téléphone au format JSON
        $jsonPhone = $serializer->serialize($phoneDetails, 'json');
        return new JsonResponse($jsonPhone, Response::HTTP_OK, [], true);
    }
}
