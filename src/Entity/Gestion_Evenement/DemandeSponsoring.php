<?php
 namespace App\Entity;

 use Doctrine\ORM\Mapping as ORM;
 use App\Entity\Evenement;
 use App\Entity\User;
 
 #[ORM\Entity]
 class DemandeSponsoring
 {
     #[ORM\Id]
     #[ORM\GeneratedValue]
     #[ORM\Column(type: 'integer')]
     private int $id;
 
     #[ORM\ManyToOne(targetEntity: User::class)]
     #[ORM\JoinColumn(name: 'sponsor', referencedColumnName: 'id', nullable: false)]
     private User $sponsor;
 
     #[ORM\ManyToOne(targetEntity: Evenement::class)]
     #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id', nullable: false)]
     private Evenement $evenement;
 
     #[ ORM\Column(type:"string", length: 255,name:"statut", nullable:true)]    
      private string $statut;
 
     #[ORM\Column(type: 'text', nullable: true)]
     private ?string $justification;
 
     // Getters & Setters
 
     public function getId(): int
     {
         return $this->id;
     }
 
     public function getSponsor(): User
     {
         return $this->sponsor;
     }
 
     public function setSponsor(User $sponsor): self
     {
         $this->sponsor = $sponsor;
         return $this;
     }
 
     public function getEvenement(): Evenement
     {
         return $this->evenement;
     }
 
     public function setEvenement(Evenement $evenement): self
     {
         $this->evenement = $evenement;
         return $this;
     }
 
     public function getStatut(): string
     {
         return $this->statut;
     }
 
     public function setStatut(string $statut): self
     {
         $this->statut = $statut;
         return $this;
     }
 
     public function getJustification(): ?string
     {
         return $this->justification;
     }
 
     public function setJustification(?string $justification): self
     {
         $this->justification = $justification;
         return $this;
     }
 }
 