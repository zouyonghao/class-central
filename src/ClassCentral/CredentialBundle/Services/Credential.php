<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 7/23/15
 * Time: 5:13 PM
 */

namespace ClassCentral\CredentialBundle\Services;



use ClassCentral\SiteBundle\Services\Kuber;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Credential {

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Gets an image from an Credential image if there is one
     * @param \ClassCentral\CredentialBundle\Entity\Credential $credential
     */
    public function getImage(\ClassCentral\CredentialBundle\Entity\Credential $credential)
    {
        $kuber = $this->container->get('kuber');
        return $kuber->getUrl(Kuber::KUBER_ENTITY_CREDENTIAL,Kuber::KUBER_TYPE_CREDENTIAL_IMAGE, $credential->getId() );
    }

    /**
     * Gets an image from the credential
     * @param \ClassCentral\CredentialBundle\Entity\Credential $credential
     */
    public function getCardImage(\ClassCentral\CredentialBundle\Entity\Credential $credential)
    {
        $kuber = $this->container->get('kuber');
        $imageService = $this->container->get('image_service');
        $credentialImage = $this->getImage($credential);
        if($credentialImage)
        {
            // TODO: Default image for credential card
            return $imageService->cropAndSaveImage(
                  $credentialImage,
                \ClassCentral\CredentialBundle\Entity\Credential::CREDENTIAL_CARD_IMAGE_HEIGHT,
                \ClassCentral\CredentialBundle\Entity\Credential::CREDENTIAL_CARD_IMAGE_WIDTH,
                Kuber::KUBER_ENTITY_CREDENTIAL,
                Kuber::KUBER_TYPE_CREDENTIAL_CARD_IMAGE,
                $credential->getId()
            );
        }

        // TODO: Default image for credential card if none exists
        return null;
    }

} 