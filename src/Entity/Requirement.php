<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RequirementRepository")
 */
class Requirement
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dispatchNumber;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $requirementNumber;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ProductRequest", mappedBy="requirement", orphanRemoval=true, cascade={"persist"})
     */
    private $productRequests;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Company", inversedBy="requirement")
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $finalCost;

    /**
     * @ORM\Column(type="datetime")
     */
    private $creationDate;

    public function __construct()
    {
        $this->productRequests = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getDispatchNumber(): ?int
    {
        return $this->dispatchNumber;
    }

    public function setDispatchNumber(?int $dispatchNumber): self
    {
        $this->dispatchNumber = $dispatchNumber;

        return $this;
    }

    public function getRequirementNumber(): ?string
    {
        return $this->requirementNumber;
    }

    public function setRequirementNumber(string $requirementNumber): self
    {
        $this->requirementNumber = $requirementNumber;

        return $this;
    }

    /**
     * @return Collection|ProductRequest[]
     */
    public function getProductRequests(): Collection
    {
        return $this->productRequests;
    }

    public function addProductRequest(ProductRequest $productRequest): self
    {
        if (!$this->productRequests->contains($productRequest)) {
            $this->productRequests[] = $productRequest;
            $productRequest->setRequirement($this);
        }

        return $this;
    }

    public function removeProductRequest(ProductRequest $productRequest): self
    {
        if ($this->productRequests->contains($productRequest)) {
            $this->productRequests->removeElement($productRequest);
            // set the owning side to null (unless already changed)
            if ($productRequest->getRequirement() === $this) {
                $productRequest->setRequirement(null);
            }
        }

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getFinalCost(): ?string
    {
        return $this->finalCost;
    }

    public function setFinalCost(string $finalCost): self
    {
        $this->finalCost = $finalCost;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeInterface $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }
}
