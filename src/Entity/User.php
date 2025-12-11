<?php

namespace App\Entity;

use App\Enum\Statut;
use App\Enum\Type;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;



#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements   UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    

#[ORM\Column(length: 100)]
#[Assert\NotBlank(message: "Le mot de passe est obligatoire.")]
#[Assert\Length(
    min: 8,
    minMessage: "Le mot de passe doit contenir au moins {{ limit }} caractères."
)]
#[Assert\Regex(
    pattern: '/[!@#$%^&*(),.?":{}|<>]/',
    message: "Le mot de passe doit contenir au moins un caractère spécial."
)]
private ?string $password = null;

    #[ORM\Column(length: 100)]
#[Assert\NotBlank(message: "Le nom est obligatoire.")]
#[Assert\Regex(
    pattern: "/^[\p{L}]+$/u",
    message: "Le nom doit contenir uniquement des lettres."
)]
private ?string $nom = null;

#[ORM\Column(length: 100)]
#[Assert\NotBlank(message: "Le prénom est obligatoire.")]
#[Assert\Regex(
    pattern: "/^[\p{L}]+$/u",
    message: "Le prénom doit contenir uniquement des lettres."
)]
private ?string $prenom = null;

#[ORM\Column]
#[Assert\NotBlank(message: "Le numéro de téléphone est obligatoire.")]
#[Assert\Regex(
    pattern: '/^[0-9]{8}$/',
    message: "Le numéro doit contenir exactement 8 chiffres."
)]
private ?string $tel = null;



  #[ORM\Column(length: 100)]
  #[Assert\NotBlank(message: "L'email est obligatoire.")]
 #[Assert\Email(
    message: "L'email '{{ value }}' n'est pas valide. Il doit contenir un '@'."
)]
private ?string $email = null;

    #[ORM\Column(enumType: Type::class)]
    private ?Type $Type = Type::Client;

    #[ORM\Column(enumType: Statut::class)]
    private ?Statut $Statut = Statut::ACTIVE;

    public function getId(): ?int
    {
        return $this->id;
    }

    

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
{
    $this->password = $password;
    return $this;
}


    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTel(): ?string
{
    return $this->tel;
}

    public function setTel(?string $tel): static
{
    $this->tel = $tel;
    return $this;
}

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getType(): ?Type
    {
        return $this->Type;
    }

    public function setType(Type $Type): static
    {
        $this->Type = $Type;

        return $this;
    }

    public function getStatut(): ?Statut
    {
        return $this->Statut;
    }

    public function setStatut(Statut $Statut): static
    {
        $this->Statut = $Statut;

        return $this;
    }
      //
      public function getUserIdentifier(): string
{
    return (string) $this->email;
}

public function getRoles(): array
{
    return ['ROLE_USER'];
}

public function eraseCredentials(): void
{
    // rien à effacer
}


}
