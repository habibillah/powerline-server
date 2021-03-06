<?php

namespace Civix\ApiBundle\Controller;

use Civix\ApiBundle\Form\Type\GroupRegistrationType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\DeferredInvites;
use Civix\CoreBundle\Model\User\UserCreator;
use Civix\CoreBundle\Model\User\BetaRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @Route("/secure")
 */
class SecureController extends BaseController
{
	private function checkEntity($entity = NULL, $username = NULL)
	{
		$em = $this->getDoctrine()->getManager();
		
		return $em->getRepository('CivixCoreBundle:' . $entity)->findOneBy(array('username' => $username));
	}
	
    /**
     * Login a entity (User, Group, Representative or SuperUser)
     * 
     * Example:
     *
     *     curl -i -X POST -H 'application/x-www-form-urlencoded' -G 'http://domain.com/api/secure/login' -d 'username=admin&password=admin'
     *
     * **Input Parameters**
     *
     *     username: the nick for the entity
     * 	   password: the password for the entity
     *
     * **Output Format**
     *
     * If successful:
     *
     *     {"token":"sometoken"}
     *
     * If error:
     *
     *     ["error","some error message"]
     *     
     * @Route("/login", name="api_secure_login")
     * @Method("POST")
     *
     * @ApiDoc(
     * 	   https = true,
     *     authentication = false,
     *     resource=true,
     *     section="Common",
     *     description="Login",
     *     views = { "default"},
     *     output = "",
     *     requirements={
	 *     },
     *     tags={
	 *         "stable" = "#89BF04",
	 *         "POST" = "#10a54a",
	 *         "login",
	 *     },
     *     filters={
     *         {"name"="username", "dataType"="string"},
     *         {"name"="password", "dataType"="string"}
     *     },
     *     parameters={
	 *     },
     *     input = {
	 *   	"class" = "",
	 *	    "options" = {"method" = "POST"},
	 *	   },
     *     statusCodes={
     *          200="Returned when successful with authorization token",
     *          400="Returned when incorrect login or password",
     *          405="Method Not Allowed"
     *     }
     * )
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        // Chain of authentication (left entity has priority over right entity)
        $entities = array('User', 'Group', 'Representative', 'Superuser');
        
        foreach($entities as $entity)
        {
        	$user = $this->checkEntity($entity, $request->get('username'));
        		
        	// As far a entity is detected, stop the checks
        	// @todo resolve conflicts naming with users, groups, etc with same name (maybe type param?)
        	if($user)
        	{
        		break;
        	}
        }
        
        // If still didn't get a entity, it is a failed authentication
        if(!$user) 
        {
            return new JsonResponse('Authentication failed.', 401); // @todo Only when Symfony 2.4 use Response::HTTP_UNAUTHORIZED
        }
        
        $encoder = $this->get('security.encoder_factory')->getEncoder($user);
        $password = $encoder->encodePassword($request->get('password'), $user->getSalt());

        if ($password === $user->getPassword()) 
        {
            $user->generateToken();
            $em->flush();
            
            return new Response($this->jmsSerialization($user, ['api-session']), 200); // @todo Only when Symfony 2.4 use Response::HTTP_OK
        }
        
        return new JsonResponse('Authentication failed.', 401); // @todo Only when Symfony 2.4 use Response::HTTP_UNAUTHORIZED
    }

    /**
     * Deprecated, use `GET /api/v2/security/facebook` instead.
     *
     * @Route("/facebook/login", name="api_secure_facebook_login")
     * @Method("POST")
     * 
     * @ApiDoc(
     *     section="Security",
     *     description="Facebook login",
     *     filters={
     *         {"name"="facebook_token", "dataType"="string"},
     *         {"name"="facebook_id", "dataType"="string"}
     *     },
     *     statusCodes={
     *         200="Returns authorization token",
     *         201="Need to register",
     *         400="Incorrect facebook token",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     */
    public function facebookLogin(Request $request)
    {
        $isTokenCorrect = $this->get('civix_core.facebook_api')->checkFacebookToken(
            $request->get('facebook_token'),
            $request->get('facebook_id')
        );

        if (!$isTokenCorrect) {
            throw new HttpException(400);
        }

        $user = $this->getDoctrine()->getManager()
                ->getRepository('CivixCoreBundle:User')
                ->getUserByFacebookId($request->get('facebook_id'));

        if ($user instanceof User) {
            $user->generateToken();
            $user->setFacebookToken($request->get('facebook_token'));
            $this->getDoctrine()->getManager()->flush();

            $response = new Response($this->jmsSerialization($user, array('api-session')));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        throw new HttpException(302);
    }

    /**
     * @Route("/registration", name="api_secure_registration")
     * @Method("POST")
     *
     * @ApiDoc(
     *      resource=true,
     *      description="Registration",
     *      filters={
     *         {"name"="username", "dataType"="string"},
     *         {"name"="first_name", "dataType"="string"},
     *         {"name"="last_name", "dataType"="string"},
     *         {"name"="email", "dataType"="string"},
     *         {"name"="password", "dataType"="string"},
     *         {"name"="address1", "dataType"="string"},
     *         {"name"="address2", "dataType"="string"},
     *         {"name"="city", "dataType"="string"},
     *         {"name"="state", "dataType"="string"},
     *         {"name"="zip", "dataType"="integer"},
     *         {"name"="country", "dataType"="string"},
     *         {"name"="facebook_id", "dataType"="string"},
     *         {"name"="facebook_token", "dataType"="string"},
     *         {"name"="facebook_link", "dataType"="string"},
     *         {"name"="birth", "dataType"="string"},
     *         {"name"="sex", "dataType"="string"},
     *         {"name"="avatar_file_name", "dataType"="string"}
     *      },
     *      statusCodes={
     *          200="Returns authorization token",
     *          400="Bad Request",
     *          405="Method Not Allowed"
     *      }
     * )
     */
    public function registrationAction(Request $request, $validatorGroups = null)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        $userCreator = UserCreator::createUserFromRequest($request);
        $user = $userCreator->create($request);

        //registration from facebook (from website)
        if (is_null($validatorGroups)) {
            $validatorGroups = $userCreator->getValidationGroups();
            $user->setIsRegistrationComplete(true);
        } else {
            $user->setIsRegistrationComplete(false);
        }

        if ($request->get('avatar_file_name')) {
            $fileInfo = explode('.', basename($request->get('avatar_file_name')));
            $fileExt = array_pop($fileInfo);
            $user->setAvatar(uniqid().'.'.$fileExt);
            try {
                $this->get('civix_core.crop_avatar')
                    ->saveSquareAvatarFromPath($user, $request->get('avatar_file_name'));
            } catch (\Exception $e) {
                $user->setAvatar(null);
                $this->get('logger')->addError($e->getMessage());
            }
        }

        $errors = $this->getValidator()->validate($user, $validatorGroups);
        if (count($errors) > 0) {
            $response->setStatusCode(400)->setContent(json_encode(array('errors' => $this->transformErrors($errors))));

            return $response;
        } else {
            $em = $this->getDoctrine()->getManager();
            $encoder = $this->get('security.encoder_factory')->getEncoder($user);
            $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
            $user->setPassword($password);
            $user->generateToken();
            $em->persist($user);
            $em->flush();

            $this->get('civix_core.user_manager')->updateDistrictsIds($user);

            $deferredInvites = $em->getRepository('CivixCoreBundle:DeferredInvites')
                    ->checkEmail($user->getEmail());

            if (!empty($deferredInvites)) {
                foreach ($deferredInvites as $invite) {
                    $user->addInvite($invite->getGroup());
                    $invite->setStatus(DeferredInvites::STATUS_INACTIVE);
                    $em->persist($user);
                    $em->persist($invite);
                }
            }

            //join to global group
            $this->get('civix_core.group_manager')->autoJoinUser($user);
            $em->persist($user);

            $em->flush();

            $response = new Response($this->jmsSerialization($user, array('api-session')));
            $response->setStatusCode(200);

            $this->get('civix_core.email_sender')
                ->sendRegistrationEmail($user);
            $this->get('event_dispatcher')->dispatch(UserEvents::REGISTRATION, new UserEvent($user));

            return $response;
        }
    }

    /**
     * Deprecated, use `GET /api/v2/security/facebook` instead.
     *
     * @Route("/registration-facebook", name="api_secure_facebook_register")
     * @Method("POST")
     * @ApiDoc(
     *     section="Security",
     *     description="Registration from facebook",
     *     filters={
     *         {"name"="facebook_token", "dataType"="string"},
     *         {"name"="facebook_id", "dataType"="string"},
     *         {"name"="country", "dataType"="string"},
     *         {"name"="username", "dataType"="string"},
     *         {"name"="email", "dataType"="string"},
     *         {"name"="first_name", "dataType"="string"},
     *         {"name"="last_name", "dataType"="string"},
     *         {"name"="avatar_file_name", "dataType"="string"},
     *         {"name"="sex", "dataType"="string"},
     *         {"name"="facebook_link", "dataType"="string"},
     *         {"name"="address1", "dataType"="string"},
     *         {"name"="city", "dataType"="string"},
     *         {"name"="state", "dataType"="string"},
     *         {"name"="zip", "dataType"="string"},
     *         {"name"="birth", "dataType"="string"},
     *         {"name"="avatar_file_name", "dataType"="string"}
     *     },
     *     statusCodes={
     *         200="Returns authorization token",
     *         400="Incorrect facebook token",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     */
    public function facebookRegistration(Request $request)
    {
        $isTokenCorrect = $this->get('civix_core.facebook_api')->checkFacebookToken(
            $request->get('facebook_token'),
            $request->get('facebook_id')
        );
        if (!$isTokenCorrect) {
            throw new HttpException(400);
        }

        $user = $this->getDoctrine()->getManager()
            ->getRepository('CivixCoreBundle:User')
            ->getUserByFacebookId($request->get('facebook_id'));
        if ($user instanceof User) {
            throw new HttpException(400);
        }

        return $this->registrationAction($request, array('facebook'));
    }

    /**
     * @Route("/forgot-password", name="api_secure_forgot_password")
     * @Method("POST")
     * @ApiDoc(
     *     resource=true,
     *     description="Forgot password",
     *     filters={
     *         {"name"="email", "dataType"="string"},
     *     },
     *     statusCodes={
     *         200="Returns success",
     *         404="Email is not found",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function forgotPassword(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('CivixCoreBundle:User')->findOneBy(array(
            'email' => $request->get('email'),
        ));
        if (!$user) {
            throw new HttpException(404);
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        //check reset expiration
        if (!$this->get('civix_core.user_manager')->checkResetInterval($user)) {
            $response->setStatusCode(400)->setContent(json_encode(array('errors' => array(array(
                    'message' => 'The password for this user has already been requested within the last 24 hours.',
                    ),
                )))
            );

            return $response;
        }

        //Generate reset token, set date of reset and sent email
        $resetPasswordToken = base_convert(bin2hex(hash('sha256', uniqid(mt_rand(), true), true)), 16, 36);
        $user->setResetPasswordToken($resetPasswordToken);
        $user->setResetPasswordAt(new \DateTime());
        $em->persist($user);
        $em->flush($user);

        //send mail
        $this->get('civix_core.email_sender')->sendResetPasswordEmail(
            $user->getEmail(),
            array(
                'name' => $user->getOfficialName(),
                'link' => $this->getWebDomain().'/#/reset-password/'.$resetPasswordToken,
            )
        );
        $response->setContent(json_encode(array('status' => 'ok')))->setStatusCode(200);

        return $response;
    }

    /**
     * @Route("/resettoken/{token}", name="api_secure_check_token")
     * @Method("GET")
     * @ApiDoc(
     *     resource=true,
     *     description="Check reset token",
     *     filters={
     *         {"name"="token", "dataType"="string"},
     *     },
     *     statusCodes={
     *         200="Returns success",
     *         404="User is not found",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function checkResetToken(Request $request)
    {
        $user = $this->getUserByResetToken($request->get('token'));

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode(array('status' => 'ok')))->setStatusCode(200);

        return $response;
    }

    /**
     * @Route("/resettoken/{token}", name="api_secure_password_update")
     * @Method("POST")
     * @ApiDoc(
     *     resource=true,
     *     description="Check reset token",
     *     filters={
     *         {"name"="token", "dataType"="string"},
     *         {"name"="password", "dataType"="string"},
     *         {"name"="passwordConfirm", "dataType"="string"}
     *     },
     *     statusCodes={
     *         200="Returns success",
     *         404="User is not found (token incorrect)",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function saveNewPassword(Request $request)
    {
        $user = $this->getUserByResetToken($request->get('token'));

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        $changePasswordModel = $this->jmsDeserialization(
            $request->getContent(),
            'Civix\CoreBundle\Model\User\ChangePassword',
            array('Default')
        );
        $errors = $this->getValidator()->validate($changePasswordModel);
        if (count($errors) > 0) {
            $response->setStatusCode(400)->setContent(json_encode(array('errors' => $this->transformErrors($errors))));

            return $response;
        } else {
            $em = $this->getDoctrine()->getManager();
            $encoder = $this->get('security.encoder_factory')->getEncoder($user);
            $password = $encoder->encodePassword($changePasswordModel->getPassword(), $user->getSalt());
            $user->setPassword($password);
            $user->setResetPasswordToken(null);
            $user->setResetPasswordAt(null);
            $em->persist($user);
            $em->flush();

            $response->setContent(json_encode(array('status' => 'ok')))->setStatusCode(200);

            return $response;
        }
    }

    /**
     * @Route("/beta", name="api_beta_request")
     * @Method("POST")
     * @ParamConverter("beta", class="Civix\CoreBundle\Model\User\BetaRequest")
     * @ApiDoc(
     *     resource=true,
     *     description="Beta request",
     *     statusCodes={
     *         201="Returns success",
     *         400="Bad Request",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function betaRequest(Request $request)
    {
        $beta = $this->jmsDeserialization($request->getContent(), BetaRequest::class, array('Default'));
        $errors = $this->getValidator()->validate($beta);
        $response = new Response(null, 201);
        $response->headers->set('Content-Type', 'application/json');

        if (count($errors) > 0) {
            $response->setStatusCode(400)->setContent(json_encode(array('errors' => $this->transformErrors($errors))));

            return $response;
        }
        $this->get('civix_core.email_sender')->sendBetaRequest($beta);

        return $response;
    }

    private function getUserByResetToken($token)
    {
        if (empty($token)) {
            throw new HttpException(404);
        }
        $user = $this->getDoctrine()->getManager()->getRepository('CivixCoreBundle:User')->findOneBy(array(
            'resetPasswordToken' => $token,
        ));
        if (!$user) {
            throw new HttpException(404);
        }

        return $user;
    }
    /**
     * Deprecated.
     *
     * @Route("/registration-group", name="civix_group_registration")
     * @Method("POST")
     *
     * @ApiDoc(
     *     section="Security",
     *     description="Register new group",
     *     input="Civix\ApiBundle\Form\Type\GroupType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"api-group"}
     *     },
     *     deprecated=true
     * )
     *
     * @View(serializerGroups={"api-group"})
     */
    public function registrationGroupAction()
    {
        $form = $this->createForm(new GroupRegistrationType(), null, [
            'validation_groups' => ['api-registration']
        ]);

        $request = $this->getRequest();
        $form->submit($request);
        
        if ($form->isValid()) {
            /** @var $group Group */
            $group = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($group);
            $em->flush();

            $this->get('civix_core.group_manager')->register($group);

            return $group;
        }
        
        return $form;
    }

    private function getWebDomain()
    {
        $request = $this->getRequest();
        $host = $request->getHttpHost();

        return $request->getScheme().'://'.str_replace('api.', '', $host);
    }
}
