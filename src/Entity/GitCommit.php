<?php

namespace App\Entity;

use App\Repository\GitCommitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GitCommitRepository::class)]
class GitCommit
{
    #[ORM\Id]
    #[ORM\Column(length:255)]
    private ?string $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $message = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Committer $committer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getCommitter(): ?Committer
    {
        return $this->committer;
    }

    public function setCommitter(Committer $committer): self
    {
        $this->committer = $committer;

        return $this;
    }
}
