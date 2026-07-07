<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Classe parente abstraite du catalogue.
 * Single Table Inheritance : une seule table "product" contient toutes les
 * colonnes des sous-types, distinguees par la colonne discriminante "product_type".
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'product_type', type: 'string', length: 20)]
#[ORM\DiscriminatorMap([
    'physical' => PhysicalCreation::class,
    'pattern' => DigitalPattern::class,
])]
abstract class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read'])]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    protected ?Category $category = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Groups(['product:read'])]
    protected ?string $name = null;

    #[ORM\Column(length: 200, unique: true)]
    #[Groups(['product:read'])]
    protected ?string $slug = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['product:detail'])]
    protected ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\Positive]
    #[Groups(['product:read'])]
    protected ?string $basePrice = null;

    #[ORM\Column]
    protected bool $isPublished = true;

    #[ORM\Column]
    #[Groups(['product:read'])]
    protected bool $isCustomizable = false;

    #[ORM\Column]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column]
    protected \DateTimeImmutable $updatedAt;

    /** @var Collection<int, ProductImage> */
    #[ORM\OneToMany(targetEntity: ProductImage::class, mappedBy: 'product', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $images;

    /** @var Collection<int, Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'products')]
    protected Collection $tags;

    /** @var Collection<int, User> */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'favorites')]
    protected Collection $favoritedBy;

    /** @var Collection<int, CustomizationRequest> */
    #[ORM\OneToMany(targetEntity: CustomizationRequest::class, mappedBy: 'product')]
    protected Collection $customizationRequests;

    /** @var Collection<int, CartItem> Cote inverse de la relation CartItem->product. */
    #[ORM\OneToMany(targetEntity: CartItem::class, mappedBy: 'product')]
    protected Collection $cartItems;

    /** @var Collection<int, OrderItem> Cote inverse de la relation OrderItem->product. */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'product')]
    protected Collection $orderItems;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->images = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->favoritedBy = new ArrayCollection();
        $this->customizationRequests = new ArrayCollection();
        $this->cartItems = new ArrayCollection();
        $this->orderItems = new ArrayCollection();
    }

    /** Retourne le type technique (implemente par chaque sous-classe). */
    #[Groups(['product:read'])]
    abstract public function getType(): string;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getBasePrice(): ?string
    {
        return $this->basePrice;
    }

    public function setBasePrice(string $basePrice): static
    {
        $this->basePrice = $basePrice;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function isCustomizable(): bool
    {
        return $this->isCustomizable;
    }

    public function setIsCustomizable(bool $isCustomizable): static
    {
        $this->isCustomizable = $isCustomizable;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): static
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /** @return Collection<int, ProductImage> */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(ProductImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProduct($this);
        }

        return $this;
    }

    public function removeImage(ProductImage $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getProduct() === $this) {
                $image->setProduct(null);
            }
        }

        return $this;
    }

    public function getMainImage(): ?ProductImage
    {
        foreach ($this->images as $image) {
            if ($image->isMain()) {
                return $image;
            }
        }

        return $this->images->first() ?: null;
    }

    /** @return Collection<int, Tag> */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    /** @return Collection<int, User> */
    public function getFavoritedBy(): Collection
    {
        return $this->favoritedBy;
    }

    /** @return Collection<int, CustomizationRequest> */
    public function getCustomizationRequests(): Collection
    {
        return $this->customizationRequests;
    }

    /** @return Collection<int, CartItem> */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    /** @return Collection<int, OrderItem> */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    /** Nom de la categorie, expose dans l'API (evite de serialiser toute l'entite Category). */
    #[Groups(['product:read'])]
    public function getCategoryName(): ?string
    {
        return $this->category?->getName();
    }

    /** Nom de fichier de l'image principale, expose dans l'API. */
    #[Groups(['product:read'])]
    public function getMainImageFilename(): ?string
    {
        return $this->getMainImage()?->getFilename();
    }
}
