<?php

namespace Citadel\Aureum\Admin\EventSubscriber;

use Citadel\Aureum\Core\Entity\Hotel;
use Forumify\Admin\Crud\Event\PreSaveCrudEvent;
use Forumify\Core\Service\MediaService;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class HotelCrudSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MediaService $mediaService,
        private readonly FilesystemOperator $hotelImageStorage,
        private readonly ParameterBagInterface $params,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [PreSaveCrudEvent::getName(Hotel::class) => 'preSaveHotel'];
    }

    /**
     * @param PreSaveCrudEvent<Hotel> $event
     */
    public function preSaveHotel(PreSaveCrudEvent $event): void
    {
        $hotel = $event->getEntity();
        $form = $event->getForm();
        $newImage = $form->get('newImage')->getData();
        if (!($newImage instanceof UploadedFile)) {
            return;
        }

        $filename = $this->mediaService->saveToFilesystem($this->hotelImageStorage, $newImage);
        $storagePath = $this->params->get('aureum.hotel.image.storage.path');
        $fullImagePath = $storagePath . '/' . $filename;

        $hotel->setLogo($fullImagePath);
    }
}
