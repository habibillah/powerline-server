<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\CoreBundle\Entity\UserPetition\Signature;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @Route("/user/user-petition-signatures")
 */
class UserPetitionSignatureController extends FOSRestController
{
    /**
     * List the authenticated user's petition signatures
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="User Petitions",
     *     description="List petition signatures of user",
     *     output="Knp\Component\Pager\Pagination\SlidingPagination",
     *     filters={
     *         {"name"="start", "dataType"="datetime", "description"="Start date"}
     *     },
     *     statusCodes={
     *         401="Unauthorized Request",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param ParamFetcher $params
     * 
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getcAction(ParamFetcher $params)
    {
        $param = new QueryParam();
        $param->requirements = new DateTime();
        $param->name = 'start';
        $params->addParam($param);

        $query = $this->getDoctrine()->getRepository(Signature::class)
            ->getFindByUserAndCriteriaQuery($this->getUser(), $params->all());
        
        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }
}
