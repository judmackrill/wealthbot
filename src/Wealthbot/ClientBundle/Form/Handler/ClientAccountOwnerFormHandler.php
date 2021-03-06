<?php
/**
 * Created by JetBrains PhpStorm.
 * User: amalyuhin
 * Date: 06.05.13
 * Time: 12:01
 * To change this template use File | Settings | File Templates.
 */

namespace Wealthbot\ClientBundle\Form\Handler;


use Doctrine\ORM\EntityManager;
use Wealthbot\ClientBundle\Entity\ClientAdditionalContact;
use Wealthbot\ClientBundle\Entity\ClientAccountOwner;
use Wealthbot\ClientBundle\Repository\ClientAdditionalContactRepository;
use Wealthbot\UserBundle\Entity\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ClientAccountOwnerFormHandler
{

    private $form;
    private $request;
    private $em;
    private $client;

    public function __construct(FormInterface $form, Request $request, EntityManager $em, User $client)
    {
        $this->form = $form;
        $this->request = $request;
        $this->em = $em;
        $this->client = $client;
    }

    /**
     * Returns array of account owners.
     * Example: array('self' => array('owner_type' => 'self', 'owner_client_id' => 1), ...)
     *
     * @return array
     */
    public function process()
    {
        $this->form->bind($this->request);
        $result = array();

        if ($this->form->isValid()) {

            $data = $this->form->getData();
            $ownerTypes = $this->form->get('owner_types')->getData();

            if (is_array($ownerTypes)) {
                foreach ($ownerTypes as $type) {
                    if ($type === ClientAccountOwner::OWNER_TYPE_OTHER) {
                        $result[$type] = $this->createOtherAccountOwner($data['other_contact']);

                    } else {
                        $result[$type] = $this->createAccountOwnerByType($type);
                    }
                }

            } else {
                $result[$ownerTypes] = $this->createAccountOwnerByType($ownerTypes);
            }
        }

        return $result;
    }

    /**
     * Create account owner with type = 'other'
     * and returns array
     *
     * @param ClientAdditionalContact $otherContact
     * @return array
     */
    private function createOtherAccountOwner(ClientAdditionalContact $otherContact)
    {
        /** @var ClientAdditionalContactRepository $repo */
        $repo = $this->em->getRepository('WealthbotClientBundle:ClientAdditionalContact');

        $exist = $repo->findOneBy(
            array(
                'client_id' => $this->client->getId(),
                'first_name' => $otherContact->getFirstName(),
                'middle_name' => $otherContact->getMiddleName(),
                'last_name' => $otherContact->getLastName(),
                'relationship' => $otherContact->getRelationship(),
                'type' => ClientAdditionalContact::TYPE_OTHER
            )
        );

        if ($exist) {
            $otherContact = $exist;
        }

        $otherContact->setType(ClientAccountOwner::OWNER_TYPE_OTHER);
        $otherContact->setClient($this->client);

        $this->em->persist($otherContact);
        $this->em->flush();

        $owner = array(
            'owner_type' => ClientAccountOwner::OWNER_TYPE_OTHER,
            'owner_contact_id' => $otherContact->getId()
        );

        return $owner;
    }

    /**
     * Create account owner by type and returns array
     *
     * @param string $type
     * @return array
     * @throws \InvalidArgumentException
     */
    private function createAccountOwnerByType($type)
    {
        $owner = array('owner_type' => $type);

        switch ($type) {
            case ClientAccountOwner::OWNER_TYPE_SELF:
                $owner['owner_client_id'] = $this->client->getId();
                break;

            case ClientAccountOwner::OWNER_TYPE_SPOUSE:
                /** @var ClientAdditionalContactRepository $repo */
                $repo = $this->em->getRepository('WealthbotClientBundle:ClientAdditionalContact');
                $spouseContact = $repo->findOneBy(array(
                    'client_id' => $this->client->getId(),
                    'type' => ClientAdditionalContact::TYPE_SPOUSE
                ));

                $owner['owner_contact_id'] = $spouseContact->getId();
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Invalid value for type argument : %s', $type));
                break;
        }

        return $owner;
    }
}