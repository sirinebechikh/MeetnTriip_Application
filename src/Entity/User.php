<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Enum\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
class User {
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(type: 'integer')]
private int $id;

#[ORM\Column(type: 'string', length: 255, name: 'nom')]
private string $nom;

#[ORM\Column(type: 'string', length: 255, unique: true, name: 'email')]
private string $email;

#[ORM\Column(type: 'string', length: 255, name: 'motDePasse')]
private string $motDePasse;

#[ORM\Column(type: 'string', length: 20, name: 'telephone')]
private ?string $telephone = null;

#[ORM\Column(type: 'string', length: 20, name: 'role')]
private string $role;

#[ORM\Column(type: 'boolean', options: ['default' => false], name: 'compteValide')]
private bool $compteValide;

#[ORM\OneToMany(mappedBy: 'user', targetEntity: Evenement::class)]
private Collection $evenements;

#[ORM\OneToMany(mappedBy: 'sponsor', targetEntity: DemandeSponsoring::class)]
private Collection $demandesSponsoring;

public function __construct()
{
$this->evenements = new ArrayCollection();
$this->demandesSponsoring = new ArrayCollection();
}

public function getId(): int { return $this->id; }
public function getNom(): string { return $this->nom; }
public function setNom(string $nom): self { $this->nom = $nom; return $this; }
public function getEmail(): string { return $this->email; }
public function setEmail(string $email): self { $this->email = $email; return $this; }
public function getMotDePasse(): string { return $this->motDePasse; }
public function setMotDePasse(string $motDePasse): self { $this->motDePasse = $motDePasse; return $this; }
public function getTelephone(): ?string { return $this->telephone; }
public function setTelephone(?string $telephone): self { $this->telephone = $telephone; return $this; }

public function getRole(): string { return $this->role; }
public function setRole(string $role): self {
if (!in_array($role, Role::getRoles())) {
throw new \InvalidArgumentException("Invalid role provided.");
}
$this->role = $role;
return $this;
}

public function isCompteValide(): bool { return $this->compteValide; }
public function setCompteValide(bool $compteValide): self { $this->compteValide = $compteValide; return $this; }

public function getEvenements(): Collection { return $this->evenements; }
public function getDemandesSponsoring(): Collection { return $this->demandesSponsoring; }
}