<?php

namespace App\Security;

use App\Component\Exceptions\MetadorException;
use App\Entity\Group;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MetadataVoter extends Voter
{
    const EDIT = 'edit';
    const VIEW = 'view';
    const EDIT_GROUP = 'edit_group';

    private $decisionManager;

    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::EDIT, self::VIEW, self::EDIT_GROUP])) {
            return false;
        }

        // only vote on Metadata array inside this voter
        if (!is_array($subject) || !isset($subject['_profile']) || !isset($subject['_source'])) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($subject, $user, $token);
            case self::EDIT:
                return $this->canEdit($subject, $user, $token);
            case self::EDIT_GROUP:
                return $this->canEditGroup($subject, $user, $token);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView($subject, $user, $token): bool
    {
        // Is public
        if ((boolean)$subject['_public'] === true || $this->canEdit($subject, $user, $token)) {
            return true;
        }

        // Same group?
        if ($user instanceof User && isset($subject['_groups'])) {
            foreach ($user->getRoles() as $role) {
                if (in_array($role, $subject['_groups'])) {
                    return true;
                }
            }
        }

        return false;
    }

    private function canEdit($subject, $user, $token): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        if (isset($subject['_locked']) && $subject['_locked']) {
            if (!isset($subject['_lock_user']) || !isset($subject['_lock_time'])) {
                throw new MetadorException('_lock_user and _lock_time are missing in Dataobject!');
            }

            $dateTime = new \DateTime();
            $timeout = ($dateTime->getTimestamp() - $subject['_lock_time']) > (int)ini_get("session.gc_maxlifetime");

            if (!$timeout && $subject['_lock_user'] !== $user->getUsername()) {
                return false;
            }
        }

        if (!isset($subject['_insert_user'])) {
            throw new MetadorException('_insert_user is missing in Dataobject!');
        }

        // can edit if user is owner
        if ($subject['_insert_user'] === $user->getUsername()) {
            return true;
        }

        // can edit if user has the role geo office
        if ($this->decisionManager->decide($token, ['ROLE_SYSTEM_GEO_OFFICE'])) {
            return true;
        }

        if (!isset($subject['_groups']) || !is_array($subject['_groups'])) {
            $subject['_groups'] = [];
        }

        // can edit if user is in the same group
        /** @var Group $group */
        foreach ($subject['_groups'] as $group) {
            if ($this->isSystemGroup($group)) {
                continue;
            }

            /** @var Group $userGroup */
            foreach ($user->getGroups() as $userGroup) {
                if ($this->isSystemGroup($userGroup->getRole())) {
                    continue;
                }

                if ($group === $userGroup->getRole()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function canEditGroup($subject, $user, $token): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        if (isset($subject['_insert_user']) && $subject['_insert_user'] == $user->getUsername()) {
            return true;
        }

        if (!isset($subject['_insert_user']) && !isset($subject['_uuid'])) {
            return true;
        }

        return false;
    }

    private function isSystemGroup($groupname): bool
    {
        return (substr($groupname, 0, 12) === 'ROLE_SYSTEM_');
    }
}
