<?php

namespace App\Entity\Security;

use App\Entity\Ecommerce\Product\PointProduct;
use App\Entity\Ecommerce\Snapshot\Product\OrderPointProductSnapshot;
use App\Entity\Ecommerce\Snapshot\UserDataSnapshot;
use App\Entity\Ecommerce\User\UserPointHistory;
use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;
use App\Attribute\Information\NoEncryptionAllowedAttribute;
use App\DTO\Security\RegisterDataDTO;
use App\Entity\Address\Address;
use App\Entity\Ecommerce\Account\Account;
use App\Entity\Ecommerce\Order;
use App\Entity\Email\Email;
use App\Entity\Email\EmailTemplate;
use App\Entity\File\UploadedFile;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Job\JobApplication;
use App\Entity\Job\JobSearchResult;
use App\Entity\Storage\AmqpStorage;
use App\Entity\Storage\Ban\BannedUserStorage;
use App\Entity\Storage\PageTrackingStorage;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Enum\File\UploadedFileSourceEnum;
use App\Enum\Points\UserPointHistoryTypeEnum;
use App\Repository\Security\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use FinancesHubBridge\Enum\PaymentStatusEnum;
use LogicException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface, EntityInterface
{

    const FIELD_EMAIL   = "email";
    const FIELD_DELETED = "deleted";
    const FIELD_ID      = "id";

    const ROLE_ADMIN          = "ROLE_ADMIN";
    const ROLE_USER           = "ROLE_USER";
    const ROLE_DEVELOPER      = "ROLE_DEVELOPER";
    const ROLE_DEBUGGER       = "ROLE_DEBUGGER";

    public const RIGHT_PUBLIC_FOLDER_ACCESS      = "RIGHT_PUBLIC_FOLDER_ACCESS";
    public const RIGHT_USER_RESET_PASSWORD       = "RIGHT_RESET_PASSWORD";
    public const RIGHT_USER_ACTIVATE_ACCOUNT     = "RIGHT_USER_ACTIVATE_ACCOUNT";

    use CreatedAndModifiedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    #[NoEncryptionAllowedAttribute]
    private string $email;

    /**
     * @ORM\Column(type="text", length=180)
     */
    #[NoEncryptionAllowedAttribute]
    private string $username;

    /**
     * @ORM\Column(type="text")
     * @Encrypted
     */
    private string $firstname;

    /**
     * @ORM\Column(type="text")
     * @Encrypted
     */
    private string $lastname;

    /**
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="text")
     * @Encrypted
     */
    private string $password;

    /**
     * @var bool $getRoleGuaranteeRoleUser
     */
    private bool $getRoleGuaranteeRoleUser = true;

    /**
     * @var DateTime|null $lastActivity
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $lastActivity;

    /**
     * @var ArrayCollection<int, PageTrackingStorage> $pageTrackingStorages
     * @ORM\OneToMany(targetEntity=PageTrackingStorage::class, mappedBy="user", cascade={"persist", "remove"})
     */
    private $pageTrackingStorages;

    /**
     * @var ArrayCollection<int, BannedUserStorage> $bannedUserStorageEntries
     * @ORM\OneToMany(targetEntity=BannedUserStorage::class, mappedBy="user", cascade={"persist", "remove"})
     */
    private $bannedUserStorageEntries;

    /**
     * @var ArrayCollection<int, UserDataSnapshot> $userDataSnapshots
     * @ORM\OneToMany(targetEntity=UserDataSnapshot::class, mappedBy="user", cascade={"persist", "remove"})
     */
    private $userDataSnapshots;

    /**
     * @ORM\OneToOne(targetEntity=Account::class, inversedBy="user", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private Account $account;

    /**
     * @var ArrayCollection<int, Order>
     * @ORM\OneToMany(targetEntity=Order::class, mappedBy="user", orphanRemoval=true)
     */
    private $orders;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $deleted = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $active = false;

    /**
     * @var ArrayCollection<int, EmailTemplate>
     * @ORM\OneToMany(targetEntity=EmailTemplate::class, mappedBy="user", orphanRemoval=true)
     */
    private $emailTemplates;

    /**
     * @var ArrayCollection<int, Email>
     * @ORM\OneToMany(targetEntity=Email::class, mappedBy="sender", orphanRemoval=true)
     */
    private $generatedEmails;

    /**
     * @var ArrayCollection<int, JobApplication>
     * @ORM\OneToMany(targetEntity=JobApplication::class, mappedBy="user", orphanRemoval=true)
     */
    private $jobApplications;

    /**
     * @var ArrayCollection<int, JobSearchResult> | PersistentCollection<int, JobSearchResult>
     * @ORM\OneToMany(targetEntity=JobSearchResult::class, mappedBy="user", orphanRemoval=true)
     */
    private $jobSearchResults;

    /**
     * @var ArrayCollection<int, AmqpStorage> $amqpStorageEntries
     * @ORM\OneToMany(targetEntity=AmqpStorage::class, mappedBy="user", cascade={"persist", "remove"})
     */
    private $amqpStorageEntries;

    /**
     * Cannot cascade remove because it's related to other users
     * Info: fetch Eager due to websocket caching db data while it has to refresh it,
     * @ORM\ManyToOne(targetEntity=Address::class, inversedBy="users", fetch="EAGER")
     */
    private Address $address;

    /**
     * @ORM\OneToMany(targetEntity=UploadedFile::class, mappedBy="user", orphanRemoval=true)
     *
     * @var Collection<int, UploadedFile>
     */
    private $uploadedFiles;

    /**
     * @ORM\Column(type="integer")
     */
    private int $pointsAmount = 0;

    /**
     * @var ArrayCollection<int, UserPointHistory>
     * @ORM\OneToMany(targetEntity=UserPointHistory::class, mappedBy="user", orphanRemoval=true)
     */
    private $pointHistory;

    /**
     * @ORM\OneToMany(targetEntity=UserRegulation::class, mappedBy="user", orphanRemoval=true)
     */
    private $regulations;

    public function __construct()
    {
        $this->initCreatedAndModified();
        $this->emailTemplates   = new ArrayCollection();
        $this->generatedEmails  = new ArrayCollection();
        $this->jobApplications  = new ArrayCollection();
        $this->jobSearchResults = new ArrayCollection();
        $this->uploadedFiles    = new ArrayCollection();
        $this->regulations      = new ArrayCollection();
        $this->pointHistory     = new ArrayCollection();

        $this->userDataSnapshots        = new ArrayCollection();
        $this->amqpStorageEntries       = new ArrayCollection();
        $this->pageTrackingStorages     = new ArrayCollection();
        $this->bannedUserStorageEntries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        if ($this->getRoleGuaranteeRoleUser) {
            $roles[] = self::ROLE_USER;
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @param string $roleName
     */
    public function addRole(string $roleName): void
    {
        if (in_array($roleName, $this->getRoles())) {
            return;
        }

        $this->roles[] = $roleName;
    }

    /**
     * Takes the role away from user
     *
     * @param string $roleName
     */
    public function removeRole(string $roleName): void
    {
        foreach ($this->roles as $index => $userRole) {
            if ($userRole === $roleName) {
                unset($this->roles[$index]);
            }
        }

        $this->roles = array_values($this->roles);
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return DateTime|null
     */
    public function getLastActivity(): ?DateTime
    {
        return $this->lastActivity;
    }

    /**
     * @param DateTime|null $lastActivity
     */
    public function setLastActivity(?DateTime $lastActivity): void
    {
        $this->lastActivity = $lastActivity;
    }

    /**
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return Collection<int, PageTrackingStorage>
     */
    public function getPageTrackingStorages(): Collection
    {
        return $this->pageTrackingStorages;
    }

    public function addPageTrackingStorage(PageTrackingStorage $pageTrackingStorage): self
    {
        if (!$this->pageTrackingStorages->contains($pageTrackingStorage)) {
            $this->pageTrackingStorages[] = $pageTrackingStorage;
            $pageTrackingStorage->setUser($this);
        }

        return $this;
    }

    public function removePageTrackingStorage(PageTrackingStorage $pageTrackingStorage): self
    {
        if ($this->pageTrackingStorages->removeElement($pageTrackingStorage)) {
            // set the owning side to null (unless already changed)
            if ($pageTrackingStorage->getUser() === $this) {
                $pageTrackingStorage->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders[] = $order;
            $order->setUser($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getUser() === $this) {
                $order->setUser(null);
            }
        }

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Will build user entity from register data
     *
     * @param RegisterDataDTO $registerDataDto
     * @param string $hashedPassword
     * @return User
     */
    public static function buildFromRegisterDto(RegisterDataDTO $registerDataDto, string $hashedPassword): User
    {
        $user = new User();
        $user->setPassword($hashedPassword);
        $user->setEmail($registerDataDto->getEmail());
        $user->setUsername($registerDataDto->getUsername());
        $user->setFirstname($registerDataDto->getFirstname());
        $user->setLastname($registerDataDto->getLastname());

        // default roles granted to user
        $user->addRole(User::RIGHT_PUBLIC_FOLDER_ACCESS);
        $user->addRole(User::RIGHT_USER_ACTIVATE_ACCOUNT);
        $user->addRole(User::RIGHT_USER_RESET_PASSWORD);

        return $user;
    }

    /**
     * @return ArrayCollection|PersistentCollection
     */
    public function getEmailTemplates(): ArrayCollection | PersistentCollection
    {
        return $this->emailTemplates;
    }

    /**
     * @return ArrayCollection|PersistentCollection
     */
    public function getActiveEmailTemplates(): ArrayCollection | PersistentCollection
    {
        return $this->emailTemplates->filter(fn (EmailTemplate $emailTemplate) => !$emailTemplate->isDeleted());
    }

    public function addEmailTemplate(EmailTemplate $emailTemplate): self
    {
        if (!$this->emailTemplates->contains($emailTemplate)) {
            $this->emailTemplates[] = $emailTemplate;
            $emailTemplate->setUser($this);
        }

        return $this;
    }

    public function removeEmailTemplate(EmailTemplate $emailTemplate): self
    {
        if ($this->emailTemplates->removeElement($emailTemplate)) {
            // set the owning side to null (unless already changed)
            if ($emailTemplate->getUser() === $this) {
                $emailTemplate->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return ArrayCollection<int, Email>
     */
    public function getGeneratedEmails(): ArrayCollection
    {
        return $this->generatedEmails;
    }

    public function addGeneratedEmail(Email $generatedEmail): self
    {
        if (!$this->generatedEmails->contains($generatedEmail)) {
            $this->generatedEmails[] = $generatedEmail;
            $generatedEmail->setSender($this);
        }

        return $this;
    }

    public function removeGeneratedEmail(Email $generatedEmail): self
    {
        if ($this->generatedEmails->removeElement($generatedEmail)) {
            // set the owning side to null (unless already changed)
            if ($generatedEmail->getSender() === $this) {
                $generatedEmail->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return ArrayCollection<int, JobApplication>
     */
    public function getJobApplications(): ArrayCollection
    {
        return $this->jobApplications;
    }

    public function addJobApplication(JobApplication $jobApplication): self
    {
        if (!$this->jobApplications->contains($jobApplication)) {
            $this->jobApplications[] = $jobApplication;
            $jobApplication->setUser($this);
        }

        return $this;
    }

    public function removeJobApplication(JobApplication $jobApplication): self
    {
        if ($this->jobApplications->removeElement($jobApplication)) {
            // set the owning side to null (unless already changed)
            if ($jobApplication->getUser() === $this) {
                $jobApplication->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return PersistentCollection<int, JobSearchResult> | ArrayCollection<int, JobSearchResult>
     */
    public function getJobSearchResults(): PersistentCollection | ArrayCollection
    {
        return $this->jobSearchResults;
    }

    public function addJobSearchResult(JobSearchResult $jobSearchResult): self
    {
        if (!$this->jobSearchResults->contains($jobSearchResult)) {
            $this->jobSearchResults[] = $jobSearchResult;
            $jobSearchResult->setUser($this);
        }

        return $this;
    }

    /**
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @param Address $address
     */
    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     */
    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     */
    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
    }

    /**
     * Will return applications made after the provided date
     *
     * @return JobApplication[]
     */
    public function getAppliedOffersAfterDate(DateTime $minDate): array
    {
        $filteredApplications = array_filter(
            $this->jobApplications->getValues(),
            fn(JobApplication $application) => ($application->getCreated()->getTimestamp() >= $minDate->getTimestamp())
        );

        return $filteredApplications;
    }

    /**
     * @param JobSearchResult $jobSearchResult
     *
     * @return array
     */
    public function getAppliedForSearch(JobSearchResult $jobSearchResult): array
    {
        return array_filter(
            $this->jobApplications->getValues(),
            fn(JobApplication $jobApplication) => $jobApplication->getJobOffer()->belongsToJobSearch($jobSearchResult)
        );
    }

    /**
     * @return Collection<int, UploadedFile>
     */
    public function getUploadedFiles(): Collection
    {
        return $this->uploadedFiles;
    }

    /**
     * Should never happen that there are more than one profile image but just in case there would be some bug
     * this would always return the latest uploaded profile image
     *
     * @return UploadedFile|null
     */
    public function getFirstProfileImage(): ?UploadedFile
    {
        /** @var UploadedFile[] $filtered */
        $filtered = $this->uploadedFiles->filter(
            fn(UploadedFile $uploadedFile) => $uploadedFile->getSource()->value === UploadedFileSourceEnum::PROFILE_IMAGE->value
        );

        $latestDate   = null;
        $selectedFile = null;
        foreach ($filtered as $uploadedFile) {
            if (
                    is_null($latestDate)
                ||  $latestDate < $uploadedFile->getCreated()->getTimestamp()
            ) {
                $latestDate   = $uploadedFile->getCreated()->getTimestamp();
                $selectedFile = $uploadedFile;
            }
        }

        return $selectedFile;
    }

    /**
     * @param UploadedFileSourceEnum $fileSourceEnum
     *
     * @return Collection
     */
    public function getUploadedFilesBySource(UploadedFileSourceEnum $fileSourceEnum): Collection
    {
        $collection = new ArrayCollection();
        foreach ($this->uploadedFiles as $uploadedFile) {
            if ($uploadedFile->getSource()->name === $fileSourceEnum->name) {
                $collection->add($uploadedFile);
            }
        }

        return $collection;
    }

    public function addUploadedFile(UploadedFile $uploadedFile): self
    {
        if (!$this->uploadedFiles->contains($uploadedFile)) {
            $this->uploadedFiles[] = $uploadedFile;
            $uploadedFile->setUser($this);
        }

        return $this;
    }

    public function removeUploadedFile(UploadedFile $uploadedFile): self
    {
        if ($this->uploadedFiles->removeElement($uploadedFile)) {
            // set the owning side to null (unless already changed)
            if ($uploadedFile->getUser() === $this) {
                $uploadedFile->setUser(null);
            }
        }

        return $this;
    }

    /**
     * Returns the user external extraction ids (job searches ids on the searcher project side),
     *
     * @return array
     */
    public function getExternalExtractionIds(): array
    {
        $ids = array_unique(array_map(
            fn(JobSearchResult $searchResult) => $searchResult->getExternalExtractionId(),
            $this->getJobSearchResults()->getValues()
        ));

        $nonEmptyIds = array_filter($ids);

        return $nonEmptyIds;
    }

    /**
     * @return Account
     */
    public function getAccount(): Account
    {
        return $this->account;
    }

    /**
     * @param Account $account
     */
    public function setAccount(Account $account): void
    {
        $this->account = $account;
    }

    /**
     * @return int
     */
    public function getPointsAmount(): int
    {
        return $this->pointsAmount;
    }

    /**
     * Will return user points, BUT NOT ONLY the ones he already has but also the one that are pending,
     *
     * @return int
     */
    public function getPointsAmountWithPendingOnes(): int
    {
        $pointsAmount = $this->getPointsAmount();
        if (!empty($this->getPendingPointsAmount())) {
            $pointsAmount = $pointsAmount + $this->getPendingPointsAmount();
        }

        return $pointsAmount;
    }

    /**
     * @param int $pointsAmount
     */
    public function setPointsAmount(int $pointsAmount): void
    {
        $this->pointsAmount = $pointsAmount;
    }

    /**
     * @param int $amount
     */
    public function addPoints(int $amount): void
    {
        $this->pointsAmount += $amount;
    }

    /**
     * @param int $amount
     */
    public function decreasePoints(int $amount): void
    {
        $this->pointsAmount -= $amount;
        if ($this->pointsAmount < 0) {
            throw new LogicException("Cannot remove more user points as it will result of having less than 0 points!");
        }
    }

    /**
     * @return bool
     */
    public function isGetRoleGuaranteeRoleUser(): bool
    {
        return $this->getRoleGuaranteeRoleUser;
    }

    /**
     * @param bool $getRoleGuaranteeRoleUser
     */
    public function setGetRoleGuaranteeRoleUser(bool $getRoleGuaranteeRoleUser): void
    {
        $this->getRoleGuaranteeRoleUser = $getRoleGuaranteeRoleUser;
    }

    /**
     * @return Collection<int, UserRegulation>
     */
    public function getRegulations(): Collection
    {
        return $this->regulations;
    }

    public function addRegulation(UserRegulation $regulation): self
    {
        if (!$this->regulations->contains($regulation)) {
            $this->regulations[] = $regulation;
            $regulation->setUser($this);
        }

        return $this;
    }

    public function removeRegulation(UserRegulation $regulation): self
    {
        if ($this->regulations->removeElement($regulation)) {
            // set the owning side to null (unless already changed)
            if ($regulation->getUser() === $this) {
                $regulation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return ArrayCollection|PersistentCollection
     */
    public function getPointHistory(): ArrayCollection|PersistentCollection
    {
        return $this->pointHistory;
    }

    /**
     * @param ArrayCollection|PersistentCollection $pointHistory
     */
    public function setPointHistory(ArrayCollection|PersistentCollection $pointHistory): void
    {
        $this->pointHistory = $pointHistory;
    }

    /**
     * @param UserPointHistory $pointHistory
     */
    public function addPointHistory(UserPointHistory $pointHistory): void
    {
        $this->pointHistory[] = $pointHistory;
    }

    /**
     * @return ArrayCollection|PersistentCollection
     */
    public function getBannedUserStorageEntries(): ArrayCollection|PersistentCollection
    {
        return $this->bannedUserStorageEntries;
    }

    /**
     * @param ArrayCollection|PersistentCollection $bannedUserStorageEntries
     */
    public function setBannedUserStorageEntries(ArrayCollection|PersistentCollection $bannedUserStorageEntries): void
    {
        $this->bannedUserStorageEntries = $bannedUserStorageEntries;
    }

    /**
     * @return ArrayCollection|PersistentCollection
     */
    public function getUserDataSnapshots(): ArrayCollection|PersistentCollection
    {
        return $this->userDataSnapshots;
    }

    /**
     * @param ArrayCollection|PersistentCollection $userDataSnapshots
     */
    public function setUserDataSnapshots(ArrayCollection|PersistentCollection $userDataSnapshots): void
    {
        $this->userDataSnapshots = $userDataSnapshots;
    }

    /**
     * @return ArrayCollection<int, AmqpStorage>|PersistentCollection<AmqpStorage>
     */
    public function getAmqpStorageEntries(): ArrayCollection|PersistentCollection
    {
        return $this->amqpStorageEntries;
    }

    /**
     * @param ArrayCollection|PersistentCollection $amqpStorageEntries
     */
    public function setAmqpStorageEntries(ArrayCollection|PersistentCollection $amqpStorageEntries): void
    {
        $this->amqpStorageEntries = $amqpStorageEntries;
    }

    /**
     * Return count of points spent overall by user
     *
     * @return int
     */
    public function countPointsSpent(): int
    {
        $sum                   = 0;
        $pointsHistoryEntities = $this->pointHistory->filter(
            fn(UserPointHistory $pointHistory) => ($pointHistory->getType() === UserPointHistoryTypeEnum::USED->name)
        );

        foreach ($pointsHistoryEntities as $entity) {
            $sum += ($entity->getAmountBefore() - $entity->getAmountNow());
        }

        return $sum;
    }

    /**
     * Returns the amount point that user got "reserved for granting" due to orders in process.
     * Basically:
     * - returns sum of points theoretically granted once payments is resolved successfully
     *
     * @return int
     */
    public function getPendingPointsAmount(): int
    {
        $pendingPoints = 0;
        foreach ($this->getOrders()->getValues() as $order) {
            if ($order->getStatus() !== PaymentStatusEnum::PENDING->name) {
                continue;
            }

            foreach ($order->getProductSnapshots() as $productSnapshot) {
                if (
                        !($productSnapshot instanceof OrderPointProductSnapshot)
                    ||  empty($productSnapshot->getProduct())
                ) {
                    continue;
                }

                /** @var OrderPointProductSnapshot $productSnapshot */
                $productSnapshot  = $productSnapshot->getProduct();
                $pendingPoints   += $productSnapshot->getAmount();
            }
        }

        return $pendingPoints;
    }

}
