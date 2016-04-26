<?php

namespace Civix\CoreBundle\Entity\Customer;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Civix\BalancedBundle\Model\BalancedUserInterface;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Customer\CustomerRepository")
 * @ORM\Table(name="customers")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 *      "representative"  = "Civix\CoreBundle\Entity\Customer\CustomerRepresentative",
 *      "group"  = "Civix\CoreBundle\Entity\Customer\CustomerGroup",
 *      "user"  = "Civix\CoreBundle\Entity\Customer\CustomerUser"
 * })
 * @Serializer\ExclusionPolicy("ALL")
 */
abstract class Customer implements BalancedUserInterface
{
    const ACCOUNT_TYPE_PERSONAL = 'personal';
    const ACCOUNT_TYPE_BUSINESS = 'business';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="balanced_uri", type="string", nullable=true)
     */
    protected $balancedUri;

    /**
     * @ORM\Column(name="account_type", type="string", nullable=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"payment-settings"})
     */
    protected $accountType;

    public static function getAccountTypes()
    {
        return [
            self::ACCOUNT_TYPE_PERSONAL,
            self::ACCOUNT_TYPE_BUSINESS,
        ];
    }
    
    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    abstract public function getUser();
    abstract public function getEmail();

    public function getBalancedUri()
    {
        return $this->balancedUri;
    }

    public function setBalancedUri($uri)
    {
        $this->balancedUri = $uri;

        return $this;
    }

    public function getUsername()
    {
        return $this->getUser()->getUsername();
    }

    /**
     * @param mixed $accountType
     *
     * @return $this
     */
    public function setAccountType($accountType)
    {
        $this->accountType = $accountType;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAccountType()
    {
        return $this->accountType;
    }
}
