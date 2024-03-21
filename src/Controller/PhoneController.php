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
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

#[Route('/api')]
class PhoneController extends AbstractController
{

    /**
     * Cette méthode permet de récupérer l'ensemble des téléphones.
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des téléphones",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Phone::class))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="La page que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Le nombre d'éléments que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Phone")
     *
     */
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

        // Convertir les données JSON en tableau
        $data = json_decode($jsonPhone, true);

        // Supprimez la clé "_links" du tableau
        unset($data['_links']);

        // Reconvertir le tableau en JSON
        $jsonWithoutLinks = json_encode($data);

        // Retourner les données de l'utilisateur sans les liens sous forme de réponse JSON
        return new JsonResponse($jsonWithoutLinks, Response::HTTP_OK, [], true);
    }
}
