<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\EventSubscriber\PrivatePageAccessSubscriber;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$connection = new class extends Connection {
    public int $queries = 0;

    public function __construct()
    {
    }

    public function fetchAssociative(string $query, array $params = [], array $types = []): array|false
    {
        ++$this->queries;
        if (!str_contains($query, 'u.is_active = TRUE')) {
            throw new RuntimeException('Le garde doit vérifier que le compte est actif.');
        }
        return match ($params['id']) {
            1 => ['id' => 1, 'company_id' => 10, 'is_admin' => false],
            2 => ['id' => 2, 'company_id' => 10, 'is_admin' => true],
            default => false,
        };
    }
};
$routes = new RouteCollection();
$routes->add('app_login', new Route('/connexion'));
$guard = new PrivatePageAccessSubscriber($connection, new UrlGenerator($routes, new RequestContext()));
$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber($guard);
$kernel = new HttpKernel($dispatcher, new ControllerResolver(), new RequestStack());
$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    ++$checks;
};
$makeRequest = static function (string $route, ?int $userId = null, string $path = '/future-page'): Request {
    $request = Request::create($path);
    $session = new Session(new MockArraySessionStorage());
    if ($userId !== null) $session->set('user_id', $userId);
    $request->setSession($session);
    $request->attributes->set('_route', $route);
    $request->attributes->set('_controller', static function (Request $request): Response {
        $request->attributes->set('controller_executed', true);
        return new Response('page privée');
    });
    return $request;
};

$public = $makeRequest('app_login');
$check($kernel->handle($public)->getStatusCode() === 200, 'La connexion doit rester publique.');
$check($connection->queries === 0, 'La connexion publique ne doit pas déclencher la recherche du compte.');

foreach (['app_home', 'app_profile', 'app_reservation', 'app_calendar', 'app_notifications', 'app_vehicle_operations', 'app_admin', 'app_future_private'] as $route) {
    $request = $makeRequest($route);
    $response = $kernel->handle($request);
    $check($response->getStatusCode() === 302 && $response->headers->get('Location') === '/connexion', "Route non protégée : $route");
    $check(!$request->attributes->get('controller_executed'), 'Le contrôleur privé ne doit pas être exécuté.');
    $check($response->headers->hasCacheControlDirective('no-store'), 'La réponse privée ne doit pas être conservée en cache.');
}

$withoutSession = Request::create('/future-page');
$withoutSession->attributes->set('_route', 'app_future_private');
$check($kernel->handle($withoutSession)->getStatusCode() === 302, 'Une requête sans session doit être refusée.');

$json = $makeRequest('app_future_api');
$json->headers->set('Accept', 'application/json');
$check($kernel->handle($json)->getStatusCode() === 401, 'Un appel JSON anonyme doit recevoir 401.');
$ajax = $makeRequest('app_future_ajax');
$ajax->headers->set('X-Requested-With', 'XMLHttpRequest');
$ajax->setMethod('POST');
$check($kernel->handle($ajax)->getStatusCode() === 401, 'Un appel AJAX POST anonyme doit recevoir 401.');

$invalid = $makeRequest('app_future_private', 999);
$check($kernel->handle($invalid)->getStatusCode() === 302, 'Un compte supprimé ou désactivé doit être refusé.');
$check(!$invalid->getSession()->has('user_id'), 'Une session devenue invalide doit être supprimée.');

$authenticated = $makeRequest('app_future_private', 1);
$response = $kernel->handle($authenticated);
$check($response->getStatusCode() === 200, 'Un compte actif doit accéder à une page privée.');
$check($authenticated->attributes->get('_authenticated_user')['company_id'] === 10, 'Le compte validé doit être transmis à la requête.');
$check($response->headers->hasCacheControlDirective('no-store'), 'Une page authentifiée ne doit pas être conservée en cache.');

foreach ([['app_admin_future', '/future-admin'], ['app_future_management', '/administration/future-page']] as [$route, $path]) {
    $employee = $makeRequest($route, 1, $path);
    $denied = false;
    try {
        $kernel->handle($employee, HttpKernelInterface::MAIN_REQUEST, false);
    } catch (NotFoundHttpException) {
        $denied = true;
    }
    $check($denied && !$employee->attributes->get('controller_executed'), 'Un employé ne doit pas accéder à une future page admin.');
    $check($kernel->handle($makeRequest($route, 2, $path))->getStatusCode() === 200, 'Un administrateur actif doit accéder à une page admin.');
}

$subrequest = $makeRequest('app_future_fragment');
$check($kernel->handle($subrequest, HttpKernelInterface::SUB_REQUEST)->getStatusCode() === 302, 'Une sous-requête privée ne doit pas contourner le garde.');

echo "$checks contrôles réussis.\n";