<?php

namespace Civix\ApiBundle\Security\Authorization\Voter;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\LeaderContentInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class LeaderContentVoter implements VoterInterface
{
    const MANAGE = 'manage';
    const MEMBER = 'member';
    const VIEW = 'view';
    /**
     * @var GroupVoter
     */
    private $groupVoter;

    public function __construct(GroupVoter $groupVoter)
    {
        $this->groupVoter = $groupVoter;
    }

    /**
     * Checks if the voter supports the given attribute.
     *
     * @param string $attribute An attribute
     *
     * @return bool    true if this Voter supports the attribute, false otherwise
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute, [
            self::MANAGE,
            self::MEMBER,
            self::VIEW,
        ]);
    }

    /**
     * Checks if the voter supports the given class.
     *
     * @param string $class A class name
     *
     * @return bool    true if this Voter can process the class
     */
    public function supportsClass($class)
    {
        $supportedClass = LeaderContentInterface::class;
        return $supportedClass === $class || is_subclass_of($class, $supportedClass);
    }

    /**
     * Returns the vote for the given parameters.
     *
     * This method must return one of the following constants:
     * ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN.
     *
     * @param TokenInterface $token A TokenInterface instance
     * @param LeaderContentInterface $object
     * @param array $attributes An array of attributes associated with the method being invoked
     *
     * @return int Either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$this->supportsClass(get_class($object))) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        if (!$this->supportsAttribute($attributes[0])) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        $group = $object->getGroup();
        if ($group instanceof Group) {
            return $this->groupVoter->vote($token, $group, $attributes);
        }

        return VoterInterface::ACCESS_DENIED;
    }
}