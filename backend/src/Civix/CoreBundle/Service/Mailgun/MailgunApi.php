<?php
/**
 * Created by PhpStorm.
 * User: sofien
 * Date: 11/10/15
 * Time: 11:50.
 */
namespace Civix\CoreBundle\Service\Mailgun;

use Mailgun\Mailgun;
use Psr\Log\LoggerInterface;

class MailgunApi
{
    const GROUP_EMAIL = '@powerlinegroups.com';

    /**
     * @var Mailgun
     */
    private $client;
    /**
     * @var Mailgun
     */
    private $publicClient;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Mailgun $client,
        Mailgun $publicClient,
        LoggerInterface $logger
    )
    {
        $this->client = $client;
        $this->publicClient = $publicClient;
        $this->logger = $logger;
    }

    public function listCreateAction($listname, $description)
    {
        $validation = $this->publicClient->get('address/validate', array(
            'address' => $listname.self::GROUP_EMAIL
        ));
        $validationresponse = json_decode(json_encode($validation), true);

        if ($validationresponse['http_response_code'] == 200
            && $validationresponse['http_response_body']['is_valid'] === false
        ) {
            return $validationresponse;
        }

        $result = $this->client->post('lists', array(
            'address' => $listname.self::GROUP_EMAIL,
            'description' => $description,
            'access_level' => 'members',
        ));

        return $this->JsonResponse($result);
    }

    public function listAddMemberAction($listname, $address, $name)
    {
        $listAddress = $listname.self::GROUP_EMAIL;

        $this->logger->info('Testing adding member '.$address);
        $result = $this->client->post("lists/$listAddress/members", array(
                'address' => $address,
                'name' => $name,
                'subscribed' => true,
                'upsert' => true,
            ));

        $this->logger->info('adding member '.$address.' '.serialize($result));

        return $this->JsonResponse($result);
    }

    public function listRemoveMemberAction($listname, $address)
    {
        $listAddress = $listname.self::GROUP_EMAIL;
        $listMember = $address;

        $result = $this->client->delete("lists/$listAddress/members/$listMember");

        return $this->JsonResponse($result);
    }

    public function listRemoveAction($listname)
    {
        $listAddress = $listname.self::GROUP_EMAIL;

        $result = $this->client->delete("lists/$listAddress");

        return $this->JsonResponse($result);
    }

    public function listAddMembersAction($listname, array $users)
    {
        $listAddress = $listname.self::GROUP_EMAIL;

        $this->logger->info('Testing adding members '.implode(', ', $users));
        $result = $this->client->post("lists/$listAddress/members.json", array(
            'members' => json_encode($users),
            'upsert' => true,
        ));

        $this->logger->info('adding members '.implode(', ', $users).' '.serialize($result));

        return $this->JsonResponse($result);
    }

    public function listExistsAction($listname)
    {
        $listAddress = $listname.self::GROUP_EMAIL;
        $result = $this->client->get('lists', array(
            'address' => $listAddress,
        ));

        $decodedresult = $this->JsonResponse($result);

        return (bool)$decodedresult['http_response_body']['total_count'];
    }

    public function JsonResponse($result)
    {
        return json_decode(json_encode($result), true);
    }
}
