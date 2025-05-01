<?php

namespace App\Entity\gestion_de_reservation;

use App\Repository\gestion_de_reservation\ConferenceLocationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConferenceLocationRepository::class)]
class ConferenceLocation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $locationId = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Name cannot be empty")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Name must be at least {{ limit }} characters long",
        maxMessage: "Name cannot be longer than {{ limit }} characters"
    )]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "City cannot be empty")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "City must be at least {{ limit }} characters long",
        maxMessage: "City cannot be longer than {{ limit }} characters"
    )]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Address cannot be empty")]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: "Address must be at least {{ limit }} characters long",
        maxMessage: "Address cannot be longer than {{ limit }} characters"
    )]
    private ?string $address = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: "Capacity cannot be empty")]
    #[Assert\Positive(message: "Capacity must be a positive number")]
    #[Assert\Range(
        min: 1,
        max: 10000,
        notInRangeMessage: "Capacity must be between {{ min }} and {{ max }}"
    )]
    private ?int $capacity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: "Price per day cannot be empty")]
    #[Assert\Positive(message: "Price must be a positive number")]
    #[Assert\Range(
        min: 0.01,
        max: 100000,
        notInRangeMessage: "Price must be between {{ min }} and {{ max }}"
    )]
    private ?string $pricePerDay = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: "Description cannot be longer than {{ limit }} characters"
    )]
    private ?string $description = null;

    public function getLocationId(): ?int
    {
        return $this->locationId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getPricePerDay(): ?string
    {
        return $this->pricePerDay;
    }

    public function setPricePerDay(string $pricePerDay): static
    {
        $this->pricePerDay = $pricePerDay;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}