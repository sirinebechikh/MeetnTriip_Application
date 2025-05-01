<?php

namespace App\Entity\gestion_de_reservation;
use App\Entity\Gestion_Evenement\Evenement;
use App\Entity\gestion_user\User;
use App\Repository\gestion_de_reservation\BookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $booking_id = null;

    // Add getId method that returns booking_id
    public function getId(): ?int
    {
        return $this->booking_id;
    }

    #[ORM\ManyToOne(targetEntity: Flight::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'flight_id', referencedColumnName: 'flight_id')]
    private ?Flight $flight = null;

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'hotel_id', referencedColumnName: 'hotel_id')]
    private ?Hotel $hotel = null;

    #[ORM\ManyToOne(targetEntity: Transport::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'transport_id', referencedColumnName: 'transport_id')]
    private ?Transport $transport = null;

    #[ORM\ManyToOne(targetEntity: ConferenceLocation::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'conference_location_id', referencedColumnName: 'location_id')]
    private ?ConferenceLocation $conferenceLocation = null;

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $booking_date = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $status = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $airlines = null;

    // Keep these column definitions as TIME type


    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $flight_price = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $hotel_name = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $hotel_location = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $hotel_price_per_night = null;

    #[ORM\Column(type: 'decimal', precision: 3, scale: 1, nullable: true)]
    private ?float $hotel_rating = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $conference_name = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $conference_price_per_day = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $transport_type = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $transport_price = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $transport_description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $priceTotal = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $name_evement = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $numberof_invites = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $start_evement = null;

    #[ORM\Column(type: 'datetime', nullable: true)] 
    private ?\DateTimeInterface $departure_time = null;
    
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $back_time = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $Special_requests = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'id_evement', referencedColumnName: 'id')]
    private ?Evenement $evenement = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'userid', referencedColumnName: 'id')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $userName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $userEmail = null;





// Getters and Setters

public function getBookingId(): ?int
{
    return $this->booking_id;
}

public function setBookingId(int $booking_id): self
{
    $this->booking_id = $booking_id;
    return $this;
}

public function getFlight(): ?Flight
{
    return $this->flight;
}

public function setFlight(?Flight $flight): self
{
    $this->flight = $flight;
    return $this;
}

public function getHotel(): ?Hotel
{
    return $this->hotel;
}

public function setHotel(?Hotel $hotel): self
{
    $this->hotel = $hotel;
    return $this;
}

public function getTransport(): ?Transport
{
    return $this->transport;
}

public function setTransport(?Transport $transport): self
{
    $this->transport = $transport;
    return $this;
}

public function getConferenceLocation(): ?ConferenceLocation
{
    return $this->conferenceLocation;
}

public function setConferenceLocation(?ConferenceLocation $conferenceLocation): self
{
    $this->conferenceLocation = $conferenceLocation;
    return $this;
}

public function getBookingDate(): ?\DateTimeInterface
{
    return $this->booking_date;
}

public function setBookingDate(\DateTimeInterface $booking_date): self
{
    $this->booking_date = $booking_date;
    return $this;
}

public function getStatus(): ?string
{
    return $this->status;
}

public function setStatus(string $status): self
{
    $this->status = $status;
    return $this;
}

public function getAirlines(): ?string
{
    return $this->airlines;
}

public function setAirlines(?string $airlines): self
{
    $this->airlines = $airlines;
    return $this;
}

public function getDepartureTime(): ?\DateTimeInterface
{
    return $this->departure_time;
}

public function setDepartureTime(?\DateTimeInterface $departure_time): self
{
    $this->departure_time = $departure_time;
    return $this;
}

public function getBackTime(): ?\DateTimeInterface
{
    return $this->back_time;
}

public function setBackTime(?\DateTimeInterface $back_time): self
{
    $this->back_time = $back_time;
    return $this;
}

public function getFlightPrice(): ?float
{
    return $this->flight_price;
}

public function setFlightPrice(?float $flight_price): self
{
    $this->flight_price = $flight_price;
    return $this;
}

public function getHotelName(): ?string
{
    return $this->hotel_name;
}

public function setHotelName(?string $hotel_name): self
{
    $this->hotel_name = $hotel_name;
    return $this;
}

public function getHotelLocation(): ?string
{
    return $this->hotel_location;
}

public function setHotelLocation(?string $hotel_location): self
{
    $this->hotel_location = $hotel_location;
    return $this;
}

public function getHotelPricePerNight(): ?float
{
    return $this->hotel_price_per_night;
}

public function setHotelPricePerNight(?float $hotel_price_per_night): self
{
    $this->hotel_price_per_night = $hotel_price_per_night;
    return $this;
}

public function getHotelRating(): ?float
{
    return $this->hotel_rating;
}

public function setHotelRating(?float $hotel_rating): self
{
    $this->hotel_rating = $hotel_rating;
    return $this;
}

public function getConferenceName(): ?string
{
    return $this->conference_name;
}

public function setConferenceName(?string $conference_name): self
{
    $this->conference_name = $conference_name;
    return $this;
}

public function getConferencePricePerDay(): ?float
{
    return $this->conference_price_per_day;
}

public function setConferencePricePerDay(?float $conference_price_per_day): self
{
    $this->conference_price_per_day = $conference_price_per_day;
    return $this;
}

public function getTransportType(): ?string
{
    return $this->transport_type;
}

public function setTransportType(?string $transport_type): self
{
    $this->transport_type = $transport_type;
    return $this;
}

public function getTransportPrice(): ?float
{
    return $this->transport_price;
}

public function setTransportPrice(?float $transport_price): self
{
    $this->transport_price = $transport_price;
    return $this;
}

public function getTransportDescription(): ?string
{
    return $this->transport_description;
}

public function setTransportDescription(?string $transport_description): self
{
    $this->transport_description = $transport_description;
    return $this;
}

public function getPriceTotal(): ?float
{
    return $this->priceTotal;
}

public function setPriceTotal(?float $priceTotal): self
{
    $this->priceTotal = $priceTotal;
    return $this;
}

public function getNameEvement(): ?string
{
    return $this->name_evement;
}

public function setNameEvement(?string $name_evement): self
{
    $this->name_evement = $name_evement;
    return $this;
}

public function getNumberofInvites(): ?int
{
    return $this->numberof_invites;
}

public function setNumberofInvites(?int $numberof_invites): self
{
    $this->numberof_invites = $numberof_invites;
    return $this;
}

public function getStartEvement(): ?\DateTimeInterface
{
    return $this->start_evement;
}

public function setStartEvement(?\DateTimeInterface $start_evement): self
{
    $this->start_evement = $start_evement;
    return $this;
}

public function getEndEvement(): ?\DateTimeInterface
{
    return $this->end_evement;
}

public function setEndEvement(?\DateTimeInterface $end_evement): self
{
    $this->end_evement = $end_evement;
    return $this;
}

public function getSpecialRequests(): ?string
{
    return $this->Special_requests;
}

public function setSpecialRequests(?string $Special_requests): self
{
    $this->Special_requests = $Special_requests;
    return $this;
}

public function getEvenement(): ?Evenement
{
    return $this->evenement;
}

public function setEvenement(?Evenement $evenement): self
{
    $this->evenement = $evenement;
    return $this;
}

public function getUser(): ?User
{
    return $this->user;
}

public function setUser(?User $user): self
{
    $this->user = $user;
    return $this;
}








// New userName property and methods
public function getUserName(): ?string
{
    return $this->userName;
}

public function setUserName(?string $userName): self
{
    $this->userName = $userName;
    return $this;
}

// Add these methods before the last closing brace
public function getUserEmail(): ?string
{
    return $this->userEmail;
}

public function setUserEmail(?string $userEmail): self
{
    $this->userEmail = $userEmail;
    return $this;
}


// Remove these boolean fields
// #[ORM\Column(type: 'boolean')]
// private bool $includeFlight = false;
// 
// #[ORM\Column(type: 'boolean')]
// private bool $includeHotel = false;
// 
// #[ORM\Column(type: 'boolean')]
// private bool $includeTransport = false;
// 
// #[ORM\Column(type: 'boolean')]
// private bool $includeConference = false;

// Add these methods after existing getters/setters
// Remove these boolean getters/setters
// public function isIncludeFlight(): bool
// {
//     return $this->includeFlight;
// }
// 
// public function setIncludeFlight(bool $includeFlight): self
// {
//     $this->includeFlight = $includeFlight;
//     return $this;
// }
//

}