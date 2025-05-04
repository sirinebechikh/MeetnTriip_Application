<?php

namespace App\Entity\gestion_de_depence;

use App\Repository\gestion_de_depence\CommentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_comment', type: 'integer')]
    private ?int $id_comment = null;

    #[ORM\Column(name: 'id_post', type: 'integer')]
    private ?int $id_post = null;

    #[ORM\Column(name: 'id_user', type: 'integer')]
    private ?int $id_user = null;

    #[ORM\Column(name: 'content', type: 'string', length: 255)]
    private ?string $content = null;

    // Getters and setters
    public function getIdComment(): ?int
    {
        return $this->id_comment;
    }

    public function getIdPost(): ?int
    {
        return $this->id_post;
    }

    public function setIdPost(int $id_post): self
    {
        $this->id_post = $id_post;
        return $this;
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
}