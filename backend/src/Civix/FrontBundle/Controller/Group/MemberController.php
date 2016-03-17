<?php

namespace Civix\FrontBundle\Controller\Group;

use Civix\CoreBundle\Event\GroupEvents;
use Civix\CoreBundle\Event\GroupUserEvent;
use Civix\CoreBundle\Exception\MailgunException;
use Cocur\Slugify\Slugify;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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
     * @Route("", name="civix_front_group_members")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Group:members/members.html.twig")
     */
    public function membersAction()
    {
        $entityManager = $this->getDoctrine()->getManager();
        /**
         * @var \Civix\CoreBundle\Entity\Group
         */
        $currentGroup = $this->getUser();

        $status = ($currentGroup->getMembershipControl() == Group::GROUP_MEMBERSHIP_APPROVAL) ?
               $currentGroup->getMembershipControl() : null;

        $query = $entityManager->getRepository('CivixCoreBundle:UserGroup')
            ->getUsersByGroupQuery($currentGroup, $status);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $this->getRequest()->get('page', 1),
            20,
            array('distinct' => false)
        );

        return array(
            'pagination' => $pagination,
            'package' => $this->get('civix_core.subscription_manager')->getPackage($this->getUser()),
        );
    }

    /**
     * @Route("/{id}/remove", name="civix_front_group_members_remove")
     * @Method({"POST"})
     */
    public function memberRemoveAction(User $user)
    {
        $entityManager = $this->getDoctrine()->getManager();

        if (!$this->getUser() instanceof \Civix\CoreBundle\Entity\Group) {
            throw $this->createNotFoundException('The group is not found');
        }

        $csrfProvider = $this->get('form.csrf_provider');
        if ($csrfProvider->isCsrfTokenValid(
            'remove_members_'.$user->getId(), $this->getRequest()->get('_token')
        )) {
            try {
                $this->get('civix_core.group_manager')
                    ->unjoinGroup($user, $this->getUser());
            } catch (MailgunException $e) {
                $this->get('session')->getFlashBag()->add('error', 'cannot remove this user from mailgun list');

                return $this->redirect($this->generateUrl('civix_front_group_members'));
            }
            $entityManager->persist($user);
            $entityManager->flush();

            $this->get('session')->getFlashBag()->add('notice', 'User has been successfully removed from group');
        } else {
            $this->get('session')->getFlashBag()->add('error', 'Something went wrong');
        }

        return $this->redirect($this->generateUrl('civix_front_group_members'));
    }

    /**
     * @Route("/approvals", name="civix_front_group_manage_approvals")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Group:members/approvals.html.twig")
     */
    public function manageApprovalsAction()
    {
        $entityManager = $this->getDoctrine()->getManager();

        $query = $entityManager->getRepository('CivixCoreBundle:UserGroup')
            ->getUsersByGroupQuery($this->getUser(), UserGroup::STATUS_PENDING);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $this->getRequest()->get('page', 1),
            20,
            array('distinct' => false)
        );

        return array(
            'pagination' => $pagination,
            'package' => $this->get('civix_core.subscription_manager')->getPackage($this->getUser()),
        );
    }

    /**
     * @Route("/{id}/approve",requirements={"id"="\d+"}, name="civix_front_group_members_approve")
     * @Method({"POST"})
     */
    public function approveUser(User $user)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $csrfProvider = $this->get('form.csrf_provider');

        if ($csrfProvider->isCsrfTokenValid(
            'approve_members_'.$user->getId(), $this->getRequest()->get('_token')
        )) {
            $userGroup = $entityManager
                ->getRepository('CivixCoreBundle:UserGroup')
                ->isJoinedUser($this->getUser(), $user);

            if ($userGroup) {
                $event = new GroupUserEvent($userGroup->getGroup(), $userGroup->getUser());
                $this->get('event_dispatcher')->dispatch(GroupEvents::USER_JOINED, $event);
                
                $userGroup->setStatus(UserGroup::STATUS_ACTIVE);
                $entityManager->persist($userGroup);
                $entityManager->flush();
                $this->get('civix_core.social_activity_manager')->noticeGroupJoiningApproved($userGroup);
                $this->get('session')->getFlashBag()->add('notice', 'User has been successfully approved');

                return $this->redirect($this->generateUrl('civix_front_group_manage_approvals'));
            }
        }

        $this->get('session')->getFlashBag()->add('error', 'Something went wrong');
    }

    /**
     * @Route("/{id}/fields",requirements={"id"="\d+"}, name="civix_front_group_members_fields")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Group:members/fields.html.twig")
     */
    public function getUserFields(User $user)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $fieldValues = $entityManager->getRepository('CivixCoreBundle:Group\FieldValue')
            ->getFieldsValuesByUser($user, $this->getUser());

        return array(
            'fieldValues' => $fieldValues,
            'user' => $user,
            'package' => $this->get('civix_core.subscription_manager')->getPackage($this->getUser()),
        );
    }

    protected function createJSONResponse($content = '', $status = 200)
    {
        $response = new Response($content, $status);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
