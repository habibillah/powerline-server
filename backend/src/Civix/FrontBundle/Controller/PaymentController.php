<?php

namespace Civix\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Civix\CoreBundle\Entity\Poll\Question\Petition;
use Civix\CoreBundle\Entity\Stripe\Customer;
use Civix\CoreBundle\Entity\Payment\Transaction;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

abstract class PaymentController extends Controller
{
    /**
     * @Route("/petitions/buy-emails/{petition}")
     * @ParamConverter(
     *      "petition",
     *      class="CivixCoreBundle:Poll\Question\Petition",
     *      options={"repository_method" = "getPublishPetitonById"}
     * )
     * @Template("CivixFrontBundle:Payment:buy-petition-emails.html.twig")
     */
    public function buyEmailsAction(Request $request, Petition $petition)
    {
        if ($petition->getUser() !== $this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        /* @var Customer $customer */
        $customer = $this->getDoctrine()
            ->getRepository(Customer::getEntityClassByUser($this->getUser()))
            ->findOneBy(['user' => $this->getUser()]);

        //get count of public emails
        $emailCount = $this->getDoctrine()
            ->getRepository('CivixCoreBundle:Poll\Question\Petition')
            ->getPetitionEmailsCount($petition);
        $amount = $emailCount * $this->get('civix_core.subscription_manager')->getPackage($this->getUser())
            ->getPetitionDataEmailPrice();

        $form = $this->createForm('form', null, ['label' => 'Buy '.$emailCount.' email(s) ('.($amount / 100).'$)']);

        if ('POST' === $request->getMethod()) {
            if (intval($request->get('amount')) !== $amount) {
                $this->get('session')->getFlashBag()->add('notice', 'Emails amount has changed. Please review.');
            } elseif ($amount < 50) {
                $this->get('session')->getFlashBag()->add('notice', 'Amount must be at least 50 cents.');
            } elseif ($form->submit($request)->isValid()) {
                $entityManager = $this->getDoctrine()->getManager();

                $charge = $this->get('civix_core.stripe')
                    ->chargeCustomer($customer, $amount, 'PowerlinePay', 'Powerline Payment:  Petition Data');
                if (!$charge->isSucceeded()) {
                    $this->get('session')->getFlashBag()->add('error', $charge->getStatus());
                } else {
                    $emails = $entityManager
                        ->getRepository('CivixCoreBundle:Poll\Question\Petition')
                        ->getPetitionEmails($petition);

                    //save transaction
                    $transaction = new Transaction();
                    $transaction->setReferencePayment(uniqid('emails_'));
                    $transaction->setStripeCustomer($customer);
                    $transaction->setData(serialize($emails));
                    $entityManager->persist($transaction);
                    $entityManager->flush($transaction);

                    return $this->redirect(
                        $this->generateUrl(
                            "civix_front_{$this->getUser()->getType()}_payment_success",
                            ['reference' => $transaction->getReferencePayment()]
                        )
                    );
                }
            }
        }

        return array(
            'petition' => $petition->getId(),
            'form' => $form->createView(),
            'amount' => $amount,
            'card' => $customer && count($customer->getCards()) ? $customer->getCards()[0] : null,
        );
    }

    /**
     * @Route("/petitions/buy-outsiders/{petition}")
     * @ParamConverter(
     *      "petition",
     *      class="CivixCoreBundle:Poll\Question\Petition"
     * )
     * @Template("CivixFrontBundle:Payment:form.html.twig")
     */
    public function buyPublicPetitionAction(Request $request, Petition $petition)
    {
        throw new AccessDeniedException();
    }

    /**
     * @Route("/petitions/transaction/success/emails/{reference}")
     * @Template("CivixFrontBundle:Payment:transactionSuccess.html.twig")
     */
    public function successAction($reference)
    {
        return array(
            'reference' => $reference,
        );
    }

    /**
     * @Route("/petitions/transaction/emails/{reference}")
     */
    public function emailsAction($reference)
    {
        $transaction = $this->getDoctrine()->getRepository('CivixCoreBundle:Payment\Transaction')
                ->findOneByReferencePayment($reference);

        if (!$transaction) {
            throw $this->createNotFoundException();
        }

        $response = $this->render(
            'CivixFrontBundle:Petition:emails.csv.twig',
            array('data' => unserialize($transaction->getData()))
        );
        $filename = 'signed_'.$transaction->getCreatedAt()->format('Y_m_d_His').'.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$filename);

        return $response;
    }

    abstract public function getCustomerClass();
}
