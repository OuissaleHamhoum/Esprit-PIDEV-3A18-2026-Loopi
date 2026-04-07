<?php

<<<<<<< HEAD
namespace App\Entity;

use App\Repository\ParticipationRepository;
use Doctrine\DBAL\Types\Types;
=======
declare(strict_types=1);

namespace App\Entity;

use App\Repository\ParticipationRepository;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
#[ORM\Table(name: 'participation')]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

<<<<<<< HEAD
    #[ORM\Column]
    private ?int $id_user = null;

    #[ORM\Column]
    private ?int $id_evenement = null;

    #[ORM\Column(length: 100)]
    private ?string $contact = null;
=======
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id_evenement', nullable: false)]
    private ?Evenement $evenement = null;

    #[ORM\Column(length: 100)]
    private string $contact = '';
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

    #[ORM\Column(nullable: true)]
    private ?int $age = null;

<<<<<<< HEAD
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_inscription = null;

    #[ORM\Column(type: 'string', columnDefinition: "enum('inscrit','present','absent')")]
    private ?string $statut = 'inscrit';
=======
    #[ORM\Column(name: 'date_inscription', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateInscription = null;

    #[ORM\Column(length: 20)]
    private string $statut = 'inscrit';
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

    public function getId(): ?int
    {
        return $this->id;
    }

<<<<<<< HEAD
    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function setIdUser(int $id_user): static
    {
        $this->id_user = $id_user;
=======
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

        return $this;
    }

<<<<<<< HEAD
    public function getIdEvenement(): ?int
    {
        return $this->id_evenement;
    }

    public function setIdEvenement(int $id_evenement): static
    {
        $this->id_evenement = $id_evenement;
=======
    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): static
    {
        $this->evenement = $evenement;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

        return $this;
    }

<<<<<<< HEAD
    public function getContact(): ?string
=======
    public function getContact(): string
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->contact;
    }

    public function setContact(string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): static
    {
        $this->age = $age;

        return $this;
    }

<<<<<<< HEAD
    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->date_inscription;
    }

    public function setDateInscription(?\DateTimeInterface $date_inscription): static
    {
        $this->date_inscription = $date_inscription;
=======
    public function getDateInscription(): ?\DateTimeImmutable
    {
        return $this->dateInscription;
    }

    public function setDateInscription(?\DateTimeImmutable $dateInscription): static
    {
        $this->dateInscription = $dateInscription;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

        return $this;
    }

<<<<<<< HEAD
    public function getStatut(): ?string
=======
    public function getStatut(): string
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
