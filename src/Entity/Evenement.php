<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'evenement')]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_evenement')]
    private ?int $id_evenement = null;

    #[ORM\Column(length: 200)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'date_evenement', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_evenement = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $lieu = null;

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
        return $this->date_evenement;
    }

    public function setDateEvenement(\DateTimeInterface $date_evenement): static
    {
        $this->date_evenement = $date_evenement;

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

    public function getIdOrganisateur(): ?int
    {
        return $this->id_organisateur;
    }

    public function setIdOrganisateur(int $id_organisateur): static
    {
        $this->id_organisateur = $id_organisateur;

        return $this;
    }

    public function getCapaciteMax(): ?int
    {
        return $this->capacite_max;
    }

    public function setCapaciteMax(?int $capacite_max): static
    {
        $this->capacite_max = $capacite_max;

        return $this;
    }

    public function getImageEvenement(): ?string
    {
        return $this->image_evenement;
    }

    public function setImageEvenement(?string $image_evenement): static
    {
        $this->image_evenement = $image_evenement;

        return $this;
    }

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

        return $this;
    }

    public function getStatutValidation(): ?string
    {
        return $this->statut_validation;
    }

    public function setStatutValidation(?string $statut_validation): static
    {
        $this->statut_validation = $statut_validation;

        return $this;
    }

    public function getDateSoumission(): ?\DateTimeInterface
    {
        return $this->date_soumission;
    }

    public function setDateSoumission(?\DateTimeInterface $date_soumission): static
    {
        $this->date_soumission = $date_soumission;

        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->date_validation;
    }

    public function setDateValidation(?\DateTimeInterface $date_validation): static
    {
        $this->date_validation = $date_validation;

        return $this;
    }

    public function getCommentaireValidation(): ?string
    {
        return $this->commentaire_validation;
    }

    public function setCommentaireValidation(?string $commentaire_validation): static
    {
        $this->commentaire_validation = $commentaire_validation;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }
}