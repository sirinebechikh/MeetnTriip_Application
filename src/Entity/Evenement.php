<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, name: 'nom')]
    private string $nom;

    #[ORM\Column(type: 'string', length: 100, name: 'type')]
    private string $type;

    #[ORM\Column(type: 'integer', name: 'nombreInvite')]
    private int $nombreInvite;

    #[ORM\Column(type: 'datetime', name: 'dateDebut')]
    private \DateTime $dateDebut;

    #[ORM\Column(type: 'datetime', name: 'dateFin')]
    private \DateTime $dateFin;

    #[ORM\Column(type: 'text', name: 'description', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'string', length: 255, name: 'lieuEvenement', nullable: true)]
    private ?string $lieuEvenement;

    #[ORM\Column(type: 'float', name: 'budgetPrevu', nullable: true)]
    private ?float $budgetPrevu;

    #[ORM\Column(type: 'text', name: 'activities', nullable: true)]
    private ?string $activities;

    #[ORM\Column(type: 'string', length: 255, name: 'imagePath', nullable: true)]
    private ?string $imagePath;

   #[ ORM\Column(type:"string", length: 255,name:"validated", nullable:true)]
   
   private ?string $validated ;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'evenements')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?User $user;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: DemandeSponsoring::class)]
    private Collection $demandesSponsoring;

    public function __construct()
    {
        $this->demandesSponsoring = new ArrayCollection();
    }

    // Getters and Setters
    public function getId(): int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    public function getNombreInvite(): int { return $this->nombreInvite; }
    public function setNombreInvite(int $nombreInvite): self { $this->nombreInvite = $nombreInvite; return $this; }
    public function getDateDebut(): \DateTime { return $this->dateDebut; }
    public function setDateDebut(\DateTime $dateDebut): self { $this->dateDebut = $dateDebut; return $this; }
    public function getDateFin(): \DateTime { return $this->dateFin; }
    public function setDateFin(\DateTime $dateFin): self { $this->dateFin = $dateFin; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getLieuEvenement(): ?string { return $this->lieuEvenement; }
    public function setLieuEvenement(?string $lieuEvenement): self { $this->lieuEvenement = $lieuEvenement; return $this; }
    public function getBudgetPrevu(): ?float { return $this->budgetPrevu; }
    public function setBudgetPrevu(?float $budgetPrevu): self { $this->budgetPrevu = $budgetPrevu; return $this; }
    public function getActivities(): ?string { return $this->activities; }
    public function setActivities(?string $activities): self { $this->activities = $activities; return $this; }
    public function getImagePath(): ?string { return $this->imagePath; }
    public function setImagePath(?string $imagePath): self { $this->imagePath = $imagePath; return $this; }
    public function isValidated(): string { return $this->validated; }
    public function setValidated(string $validated): self { $this->validated = $validated; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getDemandesSponsoring(): Collection { return $this->demandesSponsoring; }
}