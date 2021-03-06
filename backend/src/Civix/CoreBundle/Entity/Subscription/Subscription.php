<?php

namespace Civix\CoreBundle\Entity\Subscription;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Entity\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="subscriptions")
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Subscription\SubscriptionRepository")
 * @Serializer\ExclusionPolicy("ALL")
 */
class Subscription
{
    const PACKAGE_TYPE_FREE = 10;
    const PACKAGE_TYPE_SILVER = 20;
    const PACKAGE_TYPE_GOLD = 30;
    const PACKAGE_TYPE_PLATINUM = 40;
    const PACKAGE_TYPE_COMMERCIAL = 50;

    public static $labels = [
        self::PACKAGE_TYPE_FREE => 'Free',
        self::PACKAGE_TYPE_SILVER => 'Silver',
        self::PACKAGE_TYPE_GOLD => 'Gold',
        self::PACKAGE_TYPE_PLATINUM => 'Platinum',
        self::PACKAGE_TYPE_COMMERCIAL => 'Commercial',
    ];

    public static $stripePlansByType = [
        self::PACKAGE_TYPE_SILVER => 'silver',
        self::PACKAGE_TYPE_GOLD => 'gold',
        self::PACKAGE_TYPE_PLATINUM => 'platinum',
    ];

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     */
    private $id;

    /**
     * @var \Civix\CoreBundle\Entity\Representative
     *
     * @ORM\OneToOne(targetEntity="Civix\CoreBundle\Entity\Representative")
     * @JoinColumn(name="representative_id", onDelete="CASCADE")
     */
    private $representative;

    /**
     * @var \Civix\CoreBundle\Entity\Group
     *
     * @ORM\OneToOne(targetEntity="Civix\CoreBundle\Entity\Group")
     * @JoinColumn(name="group_id", onDelete="CASCADE")
     */
    private $group;

    /**
     * @var int
     *
     * @ORM\Column(name="package_type", type="integer")
     * @Assert\NotBlank()
     * @Serializer\Expose()
     * @Serializer\Type("string")
     * @Serializer\Accessor(getter="getPackageTypeName")
     */
    private $packageType;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expired_at", type="datetime")
     * @Serializer\Expose()
     */
    private $expiredAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="next_payment_at", type="datetime", nullable=true)
     * @Serializer\Expose()
     */
    private $nextPaymentAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean")
     * @Serializer\Expose()
     */
    private $enabled;

    /**
     * @ORM\Column(name="stripe_id", type="string", nullable=true)
     */
    private $stripeId;

    /**
     * @ORM\Column(name="stripe_data", type="json_array", nullable=true)
     */
    private $stripeData;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="stripe_sync_at", type="datetime", nullable=true)
     */
    private $stripeSyncAt;

    /**
     * @var string
     */
    private $coupon;

    public static function getStripePackageTypes()
    {
        return self::$stripePlansByType;
    }

    public static function getPackageTypes()
    {
        return [
            self::PACKAGE_TYPE_FREE,
            self::PACKAGE_TYPE_SILVER,
            self::PACKAGE_TYPE_GOLD,
            self::PACKAGE_TYPE_PLATINUM,
            self::PACKAGE_TYPE_COMMERCIAL,
        ];
    }
    
    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param \DateTime $expiredAt
     *
     * @return $this
     */
    public function setExpiredAt($expiredAt)
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpiredAt()
    {
        return $this->expiredAt;
    }

    /**
     * @param \Civix\CoreBundle\Entity\Group $group
     *
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return \Civix\CoreBundle\Entity\Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $packageType
     *
     * @return $this
     */
    public function setPackageType($packageType)
    {
        $this->packageType = $packageType;

        return $this;
    }

    /**
     * @return int
     */
    public function getPackageType()
    {
        return $this->packageType;
    }

    /**
     * @param \Civix\CoreBundle\Entity\Representative $representative
     *
     * @return $this
     */
    public function setRepresentative($representative)
    {
        $this->representative = $representative;

        return $this;
    }

    /**
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function getRepresentative()
    {
        return $this->representative;
    }

    /**
     * @param \DateTime $nextPaymentAt
     *
     * @return $this
     */
    public function setNextPaymentAt($nextPaymentAt)
    {
        $this->nextPaymentAt = $nextPaymentAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getNextPaymentAt()
    {
        return $this->nextPaymentAt;
    }

    /**
     * @return mixed
     */
    public function getStripeId()
    {
        return $this->stripeId;
    }

    /**
     * @param mixed $stripeId
     *
     * @return $this
     */
    public function setStripeId($stripeId)
    {
        $this->stripeId = $stripeId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStripeData()
    {
        return $this->stripeData;
    }

    /**
     * @param mixed $stripeData
     *
     * @return $this
     */
    public function setStripeData($stripeData)
    {
        $this->stripeData = $stripeData;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStripeSyncAt()
    {
        return $this->stripeSyncAt;
    }

    /**
     * @param mixed $stripeSyncAt
     *
     * @return $this
     */
    public function setStripeSyncAt($stripeSyncAt)
    {
        $this->stripeSyncAt = $stripeSyncAt;

        return $this;
    }

    /**
     * @return \Civix\CoreBundle\Entity\Group|\Civix\CoreBundle\Entity\Representative
     */
    public function getUserEntity()
    {
        if ($this->group) {
            return $this->group;
        }

        if ($this->representative) {
            return $this->representative;
        }
    }

    /**
     * @param UserInterface $entity
     *
     * @return $this
     */
    public function setUserEntity(UserInterface $entity)
    {
        $method = 'set'.ucfirst($entity->getType());
        $this->$method($entity);

        return $this;
    }

    public function isActive()
    {
        return $this->expiredAt && $this->expiredAt > new \DateTime();
    }

    public function isSyncNeeded()
    {
        return $this->stripeSyncAt && $this->stripeSyncAt < new \DateTime();
    }

    public function syncStripeData($so)
    {
        $this->stripeId = $so->id;

        $this->stripeData = [
            'status' => $so->status,
            'cancel_at_period_end' => $so->cancel_at_period_end,
            'current_period_start' => $so->current_period_start,
            'current_period_end' => $so->current_period_end,
            'ended_at' => $so->ended_at,
            'trial_start' => $so->trial_start,
            'trial_end' => $so->trial_end,
            'canceled_at' => $so->canceled_at,
            'quantity' => $so->quantity,
            'discount' => $so->discount,
        ];

        $this->stripeSyncAt = $this->stripeData['current_period_end'] ?
            (new \DateTime())->setTimestamp($this->stripeData['current_period_end']) :
            null;

        if ($this->stripeSyncAt && $this->stripeSyncAt < new \DateTime()) {
            $this->stripeSyncAt = null;
        }

        if ($this->stripeData['current_period_end']) {
            $this->expiredAt = (new \DateTime())->setTimestamp($this->stripeData['current_period_end']);
        }

        $this->enabled = 'active' === $this->stripeData['status'] &&
            !$this->stripeData['cancel_at_period_end'];
    }

    public function stripeReset()
    {
        $this->stripeSyncAt = null;
        $this->stripeId = null;
        $this->enabled = false;
        $this->stripeData = [];
    }

    public function isNotFree()
    {
        return $this->packageType !== self::PACKAGE_TYPE_FREE;
    }

    public function getLabel()
    {
        return self::$labels[$this->getPackageType()];
    }

    public function getPlanId()
    {
        return self::$stripePlansByType[$this->getPackageType()];
    }

    public function getPackageTypeName()
    {
        if (isset(self::$stripePlansByType[$this->getPackageType()])) {
            return self::$stripePlansByType[$this->getPackageType()];
        }
        
        return null;
    }

    /**
     * @return string
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @param string $coupon
     * @return Subscription
     */
    public function setCoupon($coupon)
    {
        $this->coupon = $coupon;

        return $this;
    }
}
