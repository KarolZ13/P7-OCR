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
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class UserController extends AbstractController
{
    #[Route('/users', name: 'app_users', methods:['GET'])]
    public function getUsersByCustomer(SerializerInterface $serializer, Security $security, TagAwareCacheInterface $cache): JsonResponse
    {
        $currentCustomer = $security->getUser();
    
        if ($currentCustomer instanceof Customer) {
            $customerId = $currentCustomer->getId();
            $idCache = "getUsersByCustomer_" . $customerId;
    
            $users = $cache->get($idCache, function (ItemInterface $item) use ($currentCustomer) {
                echo("L'élément n'est pas encore en cache !\n");
                $item->tag("userCache");
                return $currentCustomer->getUsers();
            });
    
            $context = SerializationContext::create()->setGroups(["getUsers"]);
            $jsonUsers = $serializer->serialize($users, 'json', $context);
            
            return new JsonResponse($jsonUsers, Response::HTTP_OK, [], true);
        } else {
            return new JsonResponse(['message' => 'Access Denied'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/user/{id}/details', name: 'app_user_details', methods:['GET'])]
    public function getUserDetailsByCustomer(User $user, SerializerInterface $serializer, UserRepository $userRepository, Security $security, TagAwareCacheInterface $cache): JsonResponse
    {
        $currentCustomer = $security->getUser();
    
        if ($currentCustomer) {
            if ($user->getCustomer() === $currentCustomer) {
                $userId = $user->getId();
                $idCache = "getUserDetailsByCustomer_" . $userId;
    
                $userDetails = $cache->get($idCache, function (ItemInterface $item) use ($userRepository, $userId) {
                    echo("L'élément n'est pas encore en cache !\n");
                    $item->tag("userDetailsCache");
                    return $userRepository->find($userId);
                });
    
                $context = SerializationContext::create()->setGroups(["getUsers"]);
                $context->setSerializeNull(true);
    
                $jsonUsers = $serializer->serialize($userDetails, 'json', $context);
    
                $data = json_decode($jsonUsers, true);

                // Supprimez la clé "_links" du tableau
                unset($data['_links']);
    
                // Reconvertir le tableau en JSON
                $jsonWithoutLinks = json_encode($data);

                return new JsonResponse($jsonWithoutLinks, Response::HTTP_OK, [], true);
            } else {
                return new JsonResponse(['message' => 'Unauthorized access to user details'], Response::HTTP_FORBIDDEN);
            }
        } else {
            return new JsonResponse(['message' => 'Access Denied'], Response::HTTP_FORBIDDEN);
        }
    } 

    #[Route('/user/add', name: 'app_customer_user_add', methods:['POST'])]
    public function addUser(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, Security $security, ValidatorInterface $validator): JsonResponse
    {
        $currentCustomer = $security->getUser();
    
        if ($currentCustomer instanceof Customer) {
            $user = $serializer->deserialize($request->getContent(), User::class, 'json');
    
            $user->setCustomer($currentCustomer);
    
            $user->setCreatedAt(new \DateTimeImmutable());

            // On vérifie les erreurs
            $errors = $validator->validate($user);

            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }

            $entityManager->persist($user);
            $entityManager->flush();
    
            $context = SerializationContext::create()->setGroups(["getUsers"]);
            $jsonUser = $serializer->serialize($user, 'json', $context);

            return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
        } else {
            return new JsonResponse(['message' => 'Access Denied'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/user/{id}/delete', name: 'app_customer_user_delete', methods:['DELETE'])]
    public function deleteUser(User $user, Security $security, EntityManagerInterface $entityManager): JsonResponse
    {
        $currentCustomer = $security->getUser();
    
        if ($currentCustomer) {
            if ($user->getCustomer() === $currentCustomer) {
    
                $entityManager->remove($user);
                $entityManager->flush();
    
                return new JsonResponse(['message' => 'User deleted successfully'], Response::HTTP_OK);
            } else {
                return new JsonResponse(['message' => 'Unauthorized access to delete user'], Response::HTTP_FORBIDDEN);
            }
        } else {
            return new JsonResponse(['message' => 'Access Denied'], Response::HTTP_FORBIDDEN);
        }
    }
}