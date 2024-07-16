<?php

namespace App\Listener\Kernel\Request;

use App\Controller\Storage\PageTrackingStorageController;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles storing the page tracking entries
 *
 * Class PageTrackingRequestListener
 * @package App\Listener\Kernel\Request
 */
class PageTrackingRequestListener implements EventSubscriberInterface
{

    const HEADER_PAGE_TRACKING_ID = "pageTrackingId";

    /**
     * @var PageTrackingStorageController $pageTrackingStorageController
     */
    private PageTrackingStorageController $pageTrackingStorageController;

    public function __construct(PageTrackingStorageController $pageTrackingStorageController)
    {
        $this->pageTrackingStorageController = $pageTrackingStorageController;
    }

    /**
     * Handle the page tracking logic

     * @param RequestEvent $requestEvent
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function onRequest(RequestEvent $requestEvent): void
    {
        $request = $requestEvent->getRequest();
        if( $this->pageTrackingStorageController->isAllowedToBeTracked($request) ){
            $pageTrackingStorageEntity = $this->pageTrackingStorageController->buildFromRequest($request);
            $this->pageTrackingStorageController->save($pageTrackingStorageEntity);
            $requestEvent->getRequest()->headers->set(self::HEADER_PAGE_TRACKING_ID, (string)$pageTrackingStorageEntity->getId());
        }
    }

    /**
     * {@inheritDoc}
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                "onRequest", -49
            ],
        ];
    }
}