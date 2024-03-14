<?php

namespace App\Controller;

use App\Entity\Customer;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use JMS\Serializer\DeserializationContext;

#[Route('/api')]
class UserController extends AbstractController
{
    #[Route('/users', name: 'app_users', methods:['GET'])]
    public function getUsersByCustomer(UserRepository $userRepository, SerializerInterface $serializer, Security $security, TagAwareCacheInterface $cache,  Request $request): JsonResponse
    {
        // Récupérer le client actuellement connecté
        $currentCustomer = $security->getUser();
    
        if ($currentCustomer instanceof Customer) {

            // Récupérer les paramètres de pagination de la requête
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 3);

            // Récupérer l'identifiant du client
            $customerId = $currentCustomer->getId();
            
            // Générer une clé de cache unique basée sur les paramètres de la requête
            $idCache = "getUsersByCustomer_customerId_{$customerId}_page_{$page}_limit_{$limit}";
    
            // Récupérer les utilisateurs depuis le cache, s'ils existent. Sinon, récupérer depuis la base de données et mettre en cache.
            $users = $cache->get($idCache, function (ItemInterface $item) use ($userRepository, $customerId, $limit, $page) {
                echo("L'élément n'est pas encore en cache !\n");
                $item->tag("userCache");
                return $userRepository->getPaginatedUsersByCustomer($customerId, $page, $limit);
            });
    
            // Définir le contexte de sérialisation pour inclure uniquement les données nécessaires
            $context = SerializationContext::create()->setGroups(["getUsers"]);
            // Sérialiser les utilisateurs au format JSON
            $jsonUsers = $serializer->serialize($users, 'json', $context);
            
            // Retourner les données des utilisateurs sous forme de réponse JSON
            return new JsonResponse($jsonUsers, Response::HTTP_OK, [], true);
        } else {
            return new JsonResponse(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/user/{id}/details', name: 'app_user_details', methods:['GET'])]
    public function getUserDetailsByCustomer(User $user, SerializerInterface $serializer, UserRepository $userRepository, Security $security, TagAwareCacheInterface $cache): JsonResponse
    {
        // Récupérer le client actuellement connecté
        $currentCustomer = $security->getUser();

        if ($currentCustomer) {
            // Vérifier si le client actuellement connecté est autorisé à voir les détails de l'utilisateur demandé
            if ($user->getCustomer() === $currentCustomer) {
                $userId = $user->getId();
                // Générer une clé de cache unique basée sur l'identifiant de l'utilisateur
                $idCache = "getUserDetailsByCustomer_" . $userId;
                // Récupérer les détails de l'utilisateur depuis le cache s'ils existent, sinon récupérer depuis la base de données et mettre en cache
                $userDetails = $cache->get($idCache, function (ItemInterface $item) use ($userRepository, $userId) {
                    echo("L'élément n'est pas encore en cache !\n");
                    $item->tag("userDetailsCache");
                    return $userRepository->find($userId);
                });
    
                // Définir le contexte de sérialisation pour inclure uniquement les données nécessaires
                $context = SerializationContext::create()->setGroups(["getUsers"]);
                $context->setSerializeNull(true);
    
                // Sérialiser les détails de l'utilisateur au format JSON
                $jsonUsers = $serializer->serialize($userDetails, 'json', $context);
    
                // Convertir les données JSON en tableau
                $data = json_decode($jsonUsers, true);

                // Supprimez la clé "_links" du tableau
                unset($data['_links']);
    
                // Reconvertir le tableau en JSON
                $jsonWithoutLinks = json_encode($data);

                // Retourner les données de l'utilisateur sans les liens sous forme de réponse JSON
                return new JsonResponse($jsonWithoutLinks, Response::HTTP_OK, [], true);
            } else {
                return new JsonResponse(['message' => 'Impossible d\'accéder aux détails de l\'utilisateur'], Response::HTTP_FORBIDDEN);
            }
        } else {
            return new JsonResponse(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }
    } 

    #[Route('/user/add', name: 'app_customer_user_add', methods:['POST'])]
    public function addUser(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, Security $security, ValidatorInterface $validator): JsonResponse
    {
        // Récupérer le client actuellement connecté
        $currentCustomer = $security->getUser();
    
        if ($currentCustomer instanceof Customer) {
            // Désérialiser les données JSON de la requête pour créer un nouvel utilisateur
            $user = $serializer->deserialize($request->getContent(), User::class, 'json');
    
            // Définir le client associé à l'utilisateur sur le client actuel
            $user->setCustomer($currentCustomer);
    
            // Définir la date de création de l'utilisateur
            $user->setCreatedAt(new \DateTimeImmutable());

            // On vérifie les erreurs
            $errors = $validator->validate($user);

            // Vérifier s'il y a des erreurs de validation
            if ($errors->count() > 0) {
                // Retourner les erreurs de validation sous forme de réponse JSON
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }

            // Persister l'utilisateur dans la base de données
            $entityManager->persist($user);
            $entityManager->flush();
    
            // Définir le contexte de sérialisation pour inclure uniquement les données nécessaires
            $context = SerializationContext::create()->setGroups(["getUsers"]);
            $jsonUser = $serializer->serialize($user, 'json', $context);

            // Retourner les données de l'utilisateur créé sous forme de réponse JSON avec le code de statut "Créé" (201)
            return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
        } else {
            return new JsonResponse(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/user/{id}/delete', name: 'app_customer_user_delete', methods:['DELETE'])]
    public function deleteUser(User $user, Security $security, EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupérer le client actuellement connecté
        $currentCustomer = $security->getUser();
    
        if ($currentCustomer) {
            if ($user->getCustomer() === $currentCustomer) {
                // Supprimer l'utilisateur de la base de données    
                $entityManager->remove($user);
                $entityManager->flush();

                // Retourner une réponse JSON vide avec le code de statut "OK" (200)
                return new JsonResponse([], Response::HTTP_OK);
            } else {
                return new JsonResponse(['message' => 'Vous n\'etes pas autorisé à supprimer l\'utilisateur'], Response::HTTP_FORBIDDEN);
            }
        } else {
            return new JsonResponse(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/user/{id}/edit', name: 'app_customer_user_edit', methods:['PUT'])]
    public function editUser(Request $request, User $currentUser, Security $security, EntityManagerInterface $entityManager, SerializerInterface $serializer, CustomerRepository $customerRepository): JsonResponse
    {
        // Récupérer le client actuellement connecté
        $currentCustomer = $security->getUser();
    
        if (!$currentCustomer instanceof Customer) {
            return new JsonResponse(['message' => 'Vous devez être authentifié en tant que client pour effectuer cette action.'], Response::HTTP_FORBIDDEN);
        }
    
        if ($currentUser->getCustomer() !== $currentCustomer) {
            return new JsonResponse(['message' => 'Vous n\'avez pas le droit de modifier cet utilisateur.'], Response::HTTP_FORBIDDEN);
        }
    
        // Désérialiser les données JSON de la requête et mettre à jour l'utilisateur existant
        $requestData = json_decode($request->getContent(), true);
    
        // Vérifier les champs autorisés à être modifiés
        $allowedFields = ['email', 'lastname', 'firstname'];
        foreach ($requestData as $property => $value) {
            if (!in_array($property, $allowedFields)) {
                return new JsonResponse(['message' => 'Erreur lors de la modification du champ "' . $property . '".'], Response::HTTP_BAD_REQUEST);
            }
        }

            // Mettre à jour les données de l'utilisateur avec les données désérialisées
        foreach ($requestData as $property => $_) {
            // Utilisation de l'accessor approprié pour définir la valeur de la propriété
            $setterMethod = 'set' . ucfirst($property);
            if (method_exists($currentUser, $setterMethod)) {
                $currentUser->$setterMethod($requestData[$property]);
            }
        }
    
        // Enregistrer les modifications dans la base de données
        $entityManager->flush();

        return new JsonResponse(['message' => 'Utilisateur mis à jour avec succès.'], JsonResponse::HTTP_OK);
    }
}