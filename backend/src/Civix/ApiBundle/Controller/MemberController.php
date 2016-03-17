<?php

namespace Civix\ApiBundle\Controller;

use Civix\CoreBundle\Event\GroupEvents;
use Civix\CoreBundle\Event\GroupUserEvent;
use Civix\CoreBundle\Exception\MailgunException;
use Cocur\Slugify\Slugify;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserGroup;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/members")
 */
class MemberController extends Controller
{
    /**
     * List all the members from a group
     * 
     * @Route("", name="civix_api_group_members")
     * @Method({"GET"})
     */
    public function membersAction()
    {
        $entityManager = $this->getDoctrine()->getManager();
        /**
         * @var $currentGroup \Civix\CoreBundle\Entity\Group
         */
        $currentGroup = $this->getUser();
        
        $status = ($currentGroup->getMembershipControl() == Group::GROUP_MEMBERSHIP_APPROVAL)?
               $currentGroup->getMembershipControl():null;
        
        $members = $entityManager->getRepository('CivixCoreBundle:UserGroup')
            ->getUsersByGroupQuery($currentGroup, $status);

        return $this->createJSONResponse($this->jmsSerialization($members, ['api-members', 'api-info']));
    }

    /**
     * Remove a user from a group name.
     * 
     * @Route("/{id}/remove", name="civix_api_group_members_remove")
     * @Method({"POST"})
     */
    public function memberRemoveAction(User $user)
    {
        $entityManager = $this->getDoctrine()->getManager();

        if (!$this->getUser() instanceof \Civix\CoreBundle\Entity\Group) 
        {
            return $this->createJSONResponse('The group is not found', 404);
        }

        try {
            $this->get('civix_core.group_manager')
                ->unjoinGroup($user, $this->getUser());
        } catch (MailgunException $e) {
            return $this->createJSONResponse('cannot remove this user from mailgun list', 404);
        }
        
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->createJSONResponse(null, 204);
    }

    /**
     * List the group member approvals
     * 
     * @Route("/approvals", name="civix_api_group_manage_approvals")
     * @Method({"GET"})
     */
    public function manageApprovalsAction()
    {
        $entityManager = $this->getDoctrine()->getManager();

        $query = $entityManager->getRepository('CivixCoreBundle:UserGroup')
            ->getUsersByGroupQuery($this->getUser(), UserGroup::STATUS_PENDING);
        
        return $this->createJSONResponse($this->jmsSerialization($members, ['api-members', 'api-info']));
    }

    /**
     * Approve a member in a group
     * 
     * @Route("/{id}/approve",requirements={"id"="\d+"}, name="civix_api_group_members_approve")
     * @Method({"POST"})
     */
    public function approveUser(User $user)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $userGroup = $entityManager
                ->getRepository('CivixCoreBundle:UserGroup')
                ->isJoinedUser($this->getUser(), $user);

        if ($userGroup) 
        {
            $event = new GroupUserEvent($userGroup->getGroup(), $userGroup->getUser());
        	$this->get('event_dispatcher')->dispatch(GroupEvents::USER_JOINED, $event);
        		
        	$userGroup->setStatus(UserGroup::STATUS_ACTIVE);
        	$entityManager->persist($userGroup);
        	$entityManager->flush();
        	
        	$this->get('civix_core.social_activity_manager')->noticeGroupJoiningApproved($userGroup);
        	
        	return $this->createJSONResponse(null, 204);
        }           

        return $this->createJSONResponse('Something went wrong', 404);
    }

    /**
     * Get the user fields for a member group
     * 
     * @Route("/{id}/fields",requirements={"id"="\d+"}, name="civix_api_group_members_fields")
     * @Method({"GET"})
     */
    public function getUserFields(User $user)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $fieldValues = $entityManager->getRepository('CivixCoreBundle:Group\FieldValue')
            ->getFieldsValuesByUser($user, $this->getUser());

        return $this->createJSONResponse($this->jmsSerialization($fieldValues, ['api-members', 'api-info']));
    }

    protected function createJSONResponse($content = '', $status = 200)
    {
        $response = new Response($content, $status);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}