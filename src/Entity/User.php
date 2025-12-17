<?php

namespace App\Entity;

use App\Enum\Statut;
use App\Enum\Type;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    #[Assert\NotBlank(message: "Password is required.")]
    #[Assert\Length(
        min: 8,
        minMessage: "Password must be at least {{ limit }} characters long."
    )]
    #[Assert\Regex(
        pattern: '/[!@#$%^&*(),.?":{}|<>]/',
        message: "Password must contain at least one special character."
    )]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Last name is required.")]
    #[Assert\Regex(
        pattern: "/^[\p{L}]+$/u",
        message: "Last name must contain only letters."
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "First name is required.")]
    #[Assert\Regex(
        pattern: "/^[\p{L}]+$/u",
        message: "First name must contain only letters."
    )]
    private ?string $prenom = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Phone number is required.")]
    #[Assert\Regex(
        pattern: '/^[0-9]{8}$/',
        message: "Phone number must contain exactly 8 digits."
    )]
    private ?string $tel = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Email is required.")]
    #[Assert\Email(
        message: "Email '{{ value }}' is not valid. It must contain '@'."
    )]
    private ?string $email = null;

    #[ORM\Column(enumType: Type::class)]
    private ?Type $Type = Type::Client;

    #[ORM\Column(enumType: Statut::class)]
    private ?Statut $Statut = Statut::ACTIVE;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Reclamation::class)]
    private Collection $reclamations;

    public function __construct()
    {
        $this->reclamations = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, Reclamation>
     */
    public function getReclamations(): Collection
    {
        return $this->reclamations;
    }

    public function addReclamation(Reclamation $reclamation): static
    {
        if (!$this->reclamations->contains($reclamation)) {
            $this->reclamations->add($reclamation);
            $reclamation->setUser($this);
        }

        return $this;
    }

    public function removeReclamation(Reclamation $reclamation): static
    {
        if ($this->reclamations->removeElement($reclamation)) {
            // set the owning side to null (unless already changed)
            if ($reclamation->getUser() === $this) {
                $reclamation->setUser(null);
            }
        }

        return $this;
    }
}
