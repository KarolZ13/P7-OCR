<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Hateoas\Configuration\Annotation as Hateoas;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "app_user_details",
 *          parameters = { "id" = "expr(object.getId())" },
 *          absolute = true
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups = {"getUsers"})
 * )
 * 
 * @Hateoas\Relation(
 *      "update",
 *      href = @Hateoas\Route(
 *          "app_customer_user_edit",
 *          parameters = { "id" = "expr(object.getId())" },
 *          absolute = true
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups = {"getUsers"})
 * )
 *
 * @Hateoas\Relation(
 *      "delete",
 *      href = @Hateoas\Route(
 *          "app_customer_user_delete",
 *          parameters = { "id" = "expr(object.getId())" },
 *          absolute = true
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups = {"getUsers"})
 * )
 */

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getUsers"])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Assert\NotBlank(message: "L'email est requis")]
    #[Assert\Email(message: "L'email doit être une adresse email valide")]
    #[Groups(["getUsers"])]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Assert\NotBlank(message: "Le prénom est requis")]
    #[Groups(["getUsers"])]
    private ?string $firstname = null;

    #[ORM\Column(length: 2558, nullable: false)]
    #[Assert\NotBlank(message: "Le nom de famille est requis")]
    #[Groups(["getUsers"])]
    private ?string $lastname = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["getUsers"])]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups(["getUsers"])]
    public function getSerializedData(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'createdAt' => $this->createdAt,
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
