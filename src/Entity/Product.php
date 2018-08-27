<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 */
class Product
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    /**
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isColor;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $uid;

    /**
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank(message="Por favor, subir imagen en .jpg, .jpeg, .png")
     * @Assert\File(mimeTypes={ "image/jpg", "image/jpeg", "image/png" })
     */
    private $attachment;

    public function getId()
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getIsColor(): ?bool
    {
        return $this->isColor;
    }

    public function setIsColor(bool $isColor): self
    {
        $this->isColor = $isColor;

        return $this;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(): self
    {
        $this->uid = md5(uniqid());

        return $this;
    }

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment($attachment): self
    {
        $this->attachment = $attachment;

        return $this;
    }
}
