<?php

namespace App\Service\Messages\Notification;

use App\DTO\Internal\WebsocketNotificationDto;
use App\Entity\Security\User;

/**
 * This interface describes the logic necessary for handling user notifications, like for example, when there is something
 * being queued and user should get an information about that over time
 */
interface NotificationInterface
{

    /**
     * Will send the notification to the user
     *
     * @param WebsocketNotificationDto $notification
     * @param User                     $user
     *
     * @return void
     */
    public function sendNotification(WebsocketNotificationDto $notification, User $user): void;
}