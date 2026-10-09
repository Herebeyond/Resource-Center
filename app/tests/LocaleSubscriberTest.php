<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\EventSubscriber\LocaleSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\HttpKernel;

$subscriber = new LocaleSubscriber();
$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber($subscriber);
$kernel = new HttpKernel($dispatcher, new ControllerResolver(), new RequestStack());
$cases = [
    ['fr', 'en-US,en;q=0.9', 'fr'],
    ['en', 'fr-FR,fr;q=0.9', 'en'],
    [null, 'fr-CA,fr;q=0.9,en;q=0.8', 'fr'],
    [null, 'en-GB,en;q=0.9', 'en'],
    ['es', 'fr-FR', 'fr'],
    ['../../fr', 'en-US', 'en'],
    [null, 'de-DE,de;q=0.9', 'en'],
    [null, null, 'en'],
];

foreach ($cases as [$cookie, $acceptLanguage, $expected]) {
    $request = Request::create('/test-locale');
    if ($cookie !== null) $request->cookies->set('resource_center_lang', $cookie);
    if ($acceptLanguage !== null) $request->headers->set('Accept-Language', $acceptLanguage);
    $request->attributes->set('_controller', static fn (Request $request): Response => new Response($request->getLocale()));
    $response = $kernel->handle($request);
    if ($response->getContent() !== $expected) {
        throw new RuntimeException('La langue du contrôleur ne respecte pas le choix autorisé ou la langue du navigateur.');
    }
}

echo "8 contrôles de langue réussis.\n";