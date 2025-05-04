<?php

namespace App\Entity\gestion_de_depence;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'post_reactions')]
class PostReaction  // Should match filename case
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_reaction', type: 'integer')]
    private ?int $idReaction = null;

    #[ORM\Column(name: 'id_post', type: 'integer')]
    private ?int $idPost = null;

    #[ORM\Column(name: 'id_user', type: 'integer')]
    private ?int $idUser = null;

    #[ORM\Column(name: 'reaction_type', type: 'string', length: 7)]
    private string $reactionType;

    #[ORM\Column(name: 'created_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getIdReaction(): ?int
    {
        return $this->idReaction;
    }

    public function getIdPost(): ?int
    {
        return $this->idPost;
    }

    public function setIdPost(int $idPost): self
    {
        $this->idPost = $idPost;
        return $this;
    }

    public function getIdUser(): ?int
    {
        return $this->idUser;
    }

    public function setIdUser(int $idUser): self
    {
        $this->idUser = $idUser;
        return $this;
    }

    public function getReactionType(): string
    {
        return $this->reactionType;
    }

    public function setReactionType(string $reactionType): self
    {
        $this->reactionType = $reactionType;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}