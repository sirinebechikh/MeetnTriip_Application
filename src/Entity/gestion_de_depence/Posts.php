<?php

namespace App\Entity\gestion_de_depence;

use App\Repository\gestion_de_depence\PostsRepository;
use App\Entity\gestion_user\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostsRepository::class)]
class Posts  // Should match filename case
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_post', type: 'integer')]
    private ?int $id_post = null;

    #[ORM\Column(name: 'id_user', type: 'integer')]
    private ?int $id_user = null;

    #[ORM\Column(name: 'content', type: 'string', length: 255)]
    private ?string $content = null;

    #[ORM\Column(name: 'type', type: 'string', length: 50)]
    private ?string $type = null;

    #[ORM\Column(name: 'media_path', type: 'string', length: 255, nullable: true)]
    private ?string $mediaPath = null;

    #[ORM\Column(name: 'media_type', type: 'string', length: 20, nullable: true)]
    private ?string $mediaType = null;

    #[ORM\Column(name: 'approval_status', type: 'string', length: 20, options: ['default' => 'pending'])]
    private string $approvalStatus = 'pending';

    // Getters and setters
    public function getIdPost(): ?int
    {
        return $this->id_post;
    }

    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function setIdUser(int $id_user): self
    {
        $this->id_user = $id_user;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id')]
    private ?User $user = null;

    // Add these methods
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getMediaPath(): ?string
    {
        return $this->mediaPath;
    }

    public function setMediaPath(?string $mediaPath): self
    {
        $this->mediaPath = $mediaPath;
        return $this;
    }

    public function getMediaType(): ?string
    {
        return $this->mediaType;
    }

    public function setMediaType(?string $mediaType): self
    {
        $this->mediaType = $mediaType;
        return $this;
    }

    public function getApprovalStatus(): string
    {
        return $this->approvalStatus;
    }

    public function setApprovalStatus(string $approvalStatus): self
    {
        $this->approvalStatus = $approvalStatus;
        return $this;
    }
}