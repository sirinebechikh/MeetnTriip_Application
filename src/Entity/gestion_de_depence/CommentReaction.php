<?php

namespace App\Entity\gestion_de_depence;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\gestion_de_depence\Comment;
use App\Entity\gestion_user\User;

#[ORM\Entity]
#[ORM\Table(name: 'comment_reactions')]
class CommentReaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_reaction', type: 'integer')]
    private $idReaction;

    #[ORM\ManyToOne(targetEntity: Comment::class)]
    #[ORM\JoinColumn(name: 'id_comment', referencedColumnName: 'id_comment', onDelete: 'CASCADE')]
    private $comment;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'commentReactions')]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private $user;

    #[ORM\Column(name: 'reaction_type', type: 'string', length: 7)]
    private $reactionType;

    #[ORM\Column(name: 'created_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // Remove these non-existent property methods
    public function getIdReaction(): ?int { return $this->idReaction; }

    public function getReactionType(): ?string { return $this->reactionType; }
    public function setReactionType(string $reactionType): self { $this->reactionType = $reactionType; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getComment(): ?Comment { return $this->comment; }
    public function setComment(?Comment $comment): self { $this->comment = $comment; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
}