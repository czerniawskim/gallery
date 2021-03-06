<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50, unique=TRUE)
     */
    private $Username;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $Password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $Email;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $resetHash;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $colorSchema = 'light';

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isDisabled = FALSE;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Gallery", mappedBy="likes")
     */
    private $likes;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Gallery", mappedBy="views")
     */
    private $views;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Gallery", mappedBy="uploader")
     */
    private $uploads;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @ORM\OneToMany(targetEntity=Reports::class, mappedBy="user")
     */
    private $reports;

    public function __construct()
    {
        $this->likes = new ArrayCollection();
        $this->views = new ArrayCollection();
        $this->uploads = new ArrayCollection();
        $this->reports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->Username;
    }

    public function setUsername(string $Username): self
    {
        $this->Username = $Username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->Password;
    }

    public function setPassword(string $Password): self
    {
        $this->Password = $Password;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->Email;
    }

    public function setEmail(string $Email): self
    {
        $this->Email = $Email;

        return $this;
    }

    public function getResetHash(): ?string
    {
        return $this->resetHash;
    }

    public function setResetHash(string $resetHash): self
    {
        $this->resetHash = $resetHash;

        return $this;
    }

    public function getColorSchema(): ?string
    {
        return $this->colorSchema;
    }

    public function setColorSchema(?string $colorSchema): self
    {
        $this->colorSchema = $colorSchema;

        return $this;
    }

    public function getIsDisabled(): ?bool
    {
        return $this->isDisabled;
    }

    public function setIsDisabled(?bool $isDisabled): self
    {
        $this->isDisabled = $isDisabled;

        return $this;
    }

    /**
     * @return Collection|Gallery[]
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(Gallery $like): self
    {
        if (!$this->likes->contains($like)) {
            $this->likes[] = $like;
            $like->addLike($this);
        }

        return $this;
    }

    public function removeLike(Gallery $like): self
    {
        if ($this->likes->contains($like)) {
            $this->likes->removeElement($like);
            $like->removeLike($this);
        }

        return $this;
    }

    /**
     * @return Collection|Gallery[]
     */
    public function getViews(): Collection
    {
        return $this->views;
    }

    public function addView(Gallery $view): self
    {
        if (!$this->views->contains($view)) {
            $this->views[] = $view;
            $view->addView($this);
        }

        return $this;
    }

    public function removeView(Gallery $view): self
    {
        if ($this->views->contains($view)) {
            $this->views->removeElement($view);
            $view->removeView($this);
        }

        return $this;
    }

    /**
     * @return Collection|Gallery[]
     */
    public function getUploads(): Collection
    {
        return $this->uploads;
    }

    public function addUpload(Gallery $gallery): self
    {
        if (!$this->gallery->contains($gallery)) {
            $this->gallery[] = $gallery;
            $gallery->setUploader($this);
        }

        return $this;
    }

    public function removeUpload(Gallery $gallery): self
    {
        if ($this->gallery->contains($gallery)) {
            $this->gallery->removeElement($gallery);
            // set the owning side to null (unless already changed)
            if ($gallery->getUploader() === $this) {
                $gallery->setUploader(null);
            }
        }

        return $this;
    }


    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = "ROLE_USER";

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getSalt()
    {
    }
    public function eraseCredentials()
    {
    }

    /**
     * @return Collection|Reports[]
     */
    public function getReports(): Collection
    {
        return $this->reports;
    }

    public function addReport(Reports $report): self
    {
        if (!$this->reports->contains($report)) {
            $this->reports[] = $report;
            $report->setUser($this);
        }

        return $this;
    }

    public function removeReport(Reports $report): self
    {
        if ($this->reports->contains($report)) {
            $this->reports->removeElement($report);
            // set the owning side to null (unless already changed)
            if ($report->getUser() === $this) {
                $report->setUser(null);
            }
        }

        return $this;
    }
}
