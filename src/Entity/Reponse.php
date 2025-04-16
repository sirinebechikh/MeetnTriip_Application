<?php

namespace App\Entity;

use App\Repository\ReponseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $idReponse = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: "Il faut remplir ce champ")]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z]+$/',
        message: "Le format du nom est incorrect. Vous devez utiliser uniquement des lettres."
    )]
    private ?string $fullname = null;

    #[ORM\ManyToOne(inversedBy: 'reponses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reclamation $reclamations = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le champ commentaire ne peut pas être vide.")]
    private ?string $commentaire = null;
    
   




   
    
      



    public function getIdReponse(): ?int
    {
        return $this->idReponse;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }
    public function getCommentaire(): ?string
{
    return $this->commentaire;
}

public function setCommentaire(string $commentaire): self
{
    $this->commentaire = $commentaire;

    return $this;
}


    public function setFullname(string $fullname): self
    {
        $this->fullname = $fullname;

        return $this;
    }

    

    public function getReclamations(): ?reclamation
    {
        return $this->reclamations;
    }

    public function setReclamations(?reclamation $s): self
    {
        $this->reclamations = $s;

        return $this;
   }

    



   
   
}
