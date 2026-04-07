<?php

<<<<<<< HEAD
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
=======
declare(strict_types=1);

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
#[ORM\Table(name: 'evenement')]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_evenement')]
<<<<<<< HEAD
    private ?int $id_evenement = null;

    #[ORM\Column(length: 200)]
    private ?string $titre = null;
=======
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private string $titre = '';
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'date_evenement', type: Types::DATETIME_MUTABLE)]
<<<<<<< HEAD
    private ?\DateTimeInterface $date_evenement = null;
=======
    private ?\DateTimeInterface $dateEvenement = null;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $lieu = null;

<<<<<<< HEAD
    #[ORM\Column(name: 'id_organisateur')]
    private ?int $id_organisateur = null;

    #[ORM\Column(name: 'capacite_max', nullable: true)]
    private ?int $capacite_max = null;

    #[ORM\Column(name: 'image_evenement', length: 255, nullable: true)]
    private ?string $image_evenement = null;

    #[ORM\Column(length: 20, options: ['default' => 'en_attente'])]
    private ?string $statut = 'en_attente';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(name: 'statut_validation', length: 20, nullable: true, options: ['default' => 'en_attente'])]
    private ?string $statut_validation = 'en_attente';

    #[ORM\Column(name: 'date_soumission', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_soumission = null;

    #[ORM\Column(name: 'date_validation', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_validation = null;

    #[ORM\Column(name: 'commentaire_validation', type: Types::TEXT, nullable: true)]
    private ?string $commentaire_validation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    private ?string $longitude = null;

    public function getIdEvenement(): ?int
    {
        return $this->id_evenement;
    }

    public function getTitre(): ?string
=======
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_organisateur', referencedColumnName: 'id', nullable: false)]
    private ?User $organisateur = null;

    #[ORM\Column(name: 'capacite_max', nullable: true)]
    private ?int $capaciteMax = null;

    #[ORM\Column(name: 'image_evenement', length: 255, nullable: true)]
    private ?string $imageEvenement = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'statut_validation', length: 20, nullable: true)]
    private ?string $statutValidation = null;

    #[ORM\Column(name: 'date_soumission', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateSoumission = null;

    #[ORM\Column(name: 'date_validation', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\Column(name: 'commentaire_validation', type: Types::TEXT, nullable: true)]
    private ?string $commentaireValidation = null;

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): string
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

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

    public function getDateEvenement(): ?\DateTimeInterface
    {
<<<<<<< HEAD
        return $this->date_evenement;
    }

    public function setDateEvenement(\DateTimeInterface $date_evenement): static
    {
        $this->date_evenement = $date_evenement;
=======
        return $this->dateEvenement;
    }

    public function setDateEvenement(\DateTimeInterface $dateEvenement): static
    {
        $this->dateEvenement = $dateEvenement;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

<<<<<<< HEAD
    public function getIdOrganisateur(): ?int
    {
        return $this->id_organisateur;
    }

    public function setIdOrganisateur(int $id_organisateur): static
    {
        $this->id_organisateur = $id_organisateur;
=======
    public function getOrganisateur(): ?User
    {
        return $this->organisateur;
    }

    public function setOrganisateur(?User $organisateur): static
    {
        $this->organisateur = $organisateur;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

        return $this;
    }

    public function getCapaciteMax(): ?int
    {
<<<<<<< HEAD
        return $this->capacite_max;
    }

    public function setCapaciteMax(?int $capacite_max): static
    {
        $this->capacite_max = $capacite_max;
=======
        return $this->capaciteMax;
    }

    public function setCapaciteMax(?int $capaciteMax): static
    {
        $this->capaciteMax = $capaciteMax;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

        return $this;
    }

    public function getImageEvenement(): ?string
    {
<<<<<<< HEAD
        return $this->image_evenement;
    }

    public function setImageEvenement(?string $image_evenement): static
    {
        $this->image_evenement = $image_evenement;
=======
        return $this->imageEvenement;
    }

    public function setImageEvenement(?string $imageEvenement): static
    {
        $this->imageEvenement = $imageEvenement;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

        return $this;
    }

<<<<<<< HEAD
    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;
=======
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

        return $this;
    }

    public function getStatutValidation(): ?string
    {
<<<<<<< HEAD
        return $this->statut_validation;
    }

    public function setStatutValidation(?string $statut_validation): static
    {
        $this->statut_validation = $statut_validation;
=======
        return $this->statutValidation;
    }

    public function setStatutValidation(?string $statutValidation): static
    {
        $this->statutValidation = $statutValidation;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

        return $this;
    }

    public function getDateSoumission(): ?\DateTimeInterface
    {
<<<<<<< HEAD
        return $this->date_soumission;
    }

    public function setDateSoumission(?\DateTimeInterface $date_soumission): static
    {
        $this->date_soumission = $date_soumission;
=======
        return $this->dateSoumission;
    }

    public function setDateSoumission(?\DateTimeInterface $dateSoumission): static
    {
        $this->dateSoumission = $dateSoumission;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
<<<<<<< HEAD
        return $this->date_validation;
    }

    public function setDateValidation(?\DateTimeInterface $date_validation): static
    {
        $this->date_validation = $date_validation;
=======
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeInterface $dateValidation): static
    {
        $this->dateValidation = $dateValidation;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

        return $this;
    }

    public function getCommentaireValidation(): ?string
    {
<<<<<<< HEAD
        return $this->commentaire_validation;
    }

    public function setCommentaireValidation(?string $commentaire_validation): static
    {
        $this->commentaire_validation = $commentaire_validation;
=======
        return $this->commentaireValidation;
    }

    public function setCommentaireValidation(?string $commentaireValidation): static
    {
        $this->commentaireValidation = $commentaireValidation;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

        return $this;
    }

<<<<<<< HEAD
    public function getLatitude(): ?string
=======
    public function getLatitude(): ?float
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->latitude;
    }

<<<<<<< HEAD
    public function setLatitude(?string $latitude): static
=======
    public function setLatitude(?float $latitude): static
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        $this->latitude = $latitude;

        return $this;
    }

<<<<<<< HEAD
    public function getLongitude(): ?string
=======
    public function getLongitude(): ?float
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->longitude;
    }

<<<<<<< HEAD
    public function setLongitude(?string $longitude): static
=======
    public function setLongitude(?float $longitude): static
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        $this->longitude = $longitude;

        return $this;
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
