<?php

namespace App\Entity;
use App\Validator\BadWords;
use App\Enum\TypeReclamation;
use App\Repository\ReclamationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: ReclamationRepository::class)]
#[Assert\Callback('validateDateReclamation')]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
   #[Assert\NotBlank(message: "Le nom est obligatoire.")]
#[Assert\Length(
    min: 3,
    minMessage: "Le nom doit comporter au moins 3 caractères."
)]
private ?string $nom = null;

    #[ORM\Column(length: 255)]
     #[Assert\NotBlank(message: "Le prénom est obligatoire.")]
    #[Assert\Regex(
        pattern: '/^[A-ZÀ-Ÿ\s]+$/u',
        message: 'Le prénom doit être en majuscules.'
    )]
    private ?string $prenom = null;

    #[ORM\Column(enumType: TypeReclamation::class)]
    #[Assert\NotBlank(message: "Le Type de Reclamation est obligatoire.")]
    private ?TypeReclamation $type_reclamation = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[BadWords(message: "Votre description contient des mots interdits.")]
    #[Assert\Length(
        min: 10,
        minMessage: "La description doit contenir au moins {{ limit }} caractères."
    )]
    private ?string $message = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
     #[Assert\NotBlank(message: "La date est obligatoire.")]
    
    private ?\DateTime $date_reclamation = null;

    #[ORM\OneToOne(mappedBy: 'reclamation', cascade: ['persist', 'remove'])]
    private ?Reponse $reponse = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTypeReclamation(): ?TypeReclamation
    {
        return $this->type_reclamation;
    }

    public function setTypeReclamation(TypeReclamation $type_reclamation): static
    {
        $this->type_reclamation = $type_reclamation;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

     public function getDateReclamation(): ?\DateTime
    {
        return $this->date_reclamation;
    }

    public function setDateReclamation(\DateTime $date_reclamation): static
    {
        $this->date_reclamation = $date_reclamation;
        return $this;
    }

    public function getReponse(): ?Reponse
    {
        return $this->reponse;
    }

    public function setReponse(Reponse $reponse): static
    {
        // set the owning side of the relation if necessary
        if ($reponse->getReclamation() !== $this) {
            $reponse->setReclamation($this);
        }

        $this->reponse = $reponse;

        return $this;
    }
     public function validateDateReclamation(ExecutionContextInterface $context, $payload)
    {
        if ($this->date_reclamation) {
            $today = new \DateTime('today');
            if ($this->date_reclamation->format('Y-m-d') !== $today->format('Y-m-d')) {
                $context->buildViolation('La date doit être égale à la date d’aujourd’hui.')
                        ->atPath('date_reclamation')
                        ->addViolation();
            }
        }
    }   
}
