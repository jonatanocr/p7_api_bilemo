<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;
use Hateoas\Configuration\Annotation as Hateoas;

/**
* @Hateoas\Relation(
*      "self",
*      href = @Hateoas\Route(
*          "detailUser",
*          parameters = { "id" = "expr(object.getId())" }
*      ),
*      exclusion = @Hateoas\Exclusion(groups="getUsers"),
* )
*
* @Hateoas\Relation(
*      "delete",
*      href = @Hateoas\Route(
*          "deleteUser",
*          parameters = { "id" = "expr(object.getId())" },
*      ),
*      exclusion = @Hateoas\Exclusion(groups="getUsers"),
* )
*
* * @Hateoas\Relation(
*      "update",
*      href = @Hateoas\Route(
*          "updateUser",
*          parameters = { "id" = "expr(object.getId())" },
*      ),
*      exclusion = @Hateoas\Exclusion(groups="getUsers"),
* )
*/
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['getUsers'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getUsers'])]
    #[Assert\NotBlank(message: "User name is mandatory")]
    #[Assert\Length(min:1, max:255, minMessage: "User name must have at least {{ limit }} characters", maxMessage: "User name max characters is {{ limit }}")]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getUsers'])]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getUsers'])]
    private ?string $telephone = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[Groups(['getUsers'])]
    private ?Client $client = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }
}
