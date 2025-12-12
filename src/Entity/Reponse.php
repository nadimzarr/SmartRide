<?php

namespace App\Entity;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\ReponseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
#[Assert\Callback('validateDate')]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\OneToOne(inversedBy: 'reponse', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reclamation $reclamation = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le contenu est obligatoire.")]
    #[Assert\Length(
        min: 10,
        minMessage: "Le contenu doit contenir au moins {{ limit }} caractères."
    )]
    private ?string $contenu = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date est obligatoire.")]
    private ?\DateTime $date_reponse = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReclamation(): ?Reclamation
    {
        return $this->reclamation;
    }

    public function setReclamation(Reclamation $reclamation): static
    {
        $this->reclamation = $reclamation;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

 public function getDateReponse(): ?\DateTime
{
    return $this->date_reponse;
}

public function setDateReponse(\DateTime $date_reponse): static
{
    $this->date_reponse = $date_reponse;
    return $this;
}
    public function validateDate(ExecutionContextInterface $context, $payload)
{
    if ($this->date_reponse && $this->date_reponse->format('Y-m-d') !== (new \DateTime())->format('Y-m-d')) {
        $context->buildViolation('La date doit être la date du jour.')
            ->atPath('date_reponse')
            ->addViolation();
    }
}
}
