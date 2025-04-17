<?php

namespace App\Entity;

 
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]

 #[ORM\Table(name: 'employee_event_assignments')]
 class EmployeeEventAssignments{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'employeeAssignments')]
    #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'id', nullable: false, onDelete: "CASCADE")]
    private ?User $employee = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'employeeAssignments')]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id', nullable: false, onDelete: "CASCADE")]
    private ?Evenement $event = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $role;

    #[ORM\Column(type: 'string', length: 50, options: ['default' => 'Assigned'])]
    private string $status = 'Assigned';

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // -------------------
    // Getters & Setters
    // -------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployee(): ?User
    {
        return $this->employee;
    }

    public function setEmployee(?User $employee): self
    {
        $this->employee = $employee;
        return $this;
    }

    public function getEvent(): ?Evenement
    {
        return $this->event;
    }

    public function setEvent(?Evenement $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}