<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['applyLocale', 20]];
    }

    public function applyLocale(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $language = $request->cookies->get('resource_center_lang');
        $request->setLocale(in_array($language, ['fr', 'en'], true)
            ? $language
            : ($request->getPreferredLanguage(['en', 'fr']) ?? 'en'));
    }
}