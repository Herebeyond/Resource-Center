<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Controller\ReservationController;

$reflection = new ReflectionClass(ReservationController::class);
$controller = $reflection->newInstanceWithoutConstructor();
$groupResources = $reflection->getMethod('groupResources');
$check = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};
$base = [
    'resource_type' => 'portable', 'equipment_brand' => 'Dell', 'equipment_model' => 'Latitude 5440',
    'equipment_category' => 'informatique', 'places' => 0, 'location' => 'Site principal',
];
$resources = [
    ['id' => 1, 'name' => 'Exemplaire 01', 'serial_number' => 'SN-01'] + $base,
    ['id' => 2, 'name' => 'Exemplaire 02', 'serial_number' => 'SN-02', 'location' => 'Annexe'] + $base,
    ['id' => 3, 'name' => 'Autre modèle', 'equipment_model' => 'Latitude 7440'] + $base,
    ['id' => 4, 'name' => 'Autre catégorie', 'equipment_category' => 'stockage'] + $base,
    ['id' => 5, 'name' => 'Autre type', 'resource_type' => 'equipement'] + $base,
    ['id' => 6, 'name' => 'Sans modèle', 'equipment_model' => null] + $base,
    ['id' => 7, 'name' => 'Sans modèle', 'equipment_model' => null] + $base,
];
$groups = $groupResources->invokeArgs($controller, [&$resources, false]);
$check(count($groups) === 6, 'Seuls les exemplaires du même modèle, type et catégorie doivent être regroupés.');
$check($groups[0]['resource_ids'] === ['1', '2'], 'Chaque identifiant physique doit rester accessible.');
$check($groups[0]['count'] === 2 && count($groups[0]['locations']) === 2, 'Le groupe doit compter ses exemplaires et leurs lieux.');
$check($resources[0]['vehicle_group_key'] === $resources[1]['vehicle_group_key'], 'Le nom numéroté et le numéro de série ne doivent pas séparer les groupes.');
$check($resources[5]['vehicle_group_key'] !== $resources[6]['vehicle_group_key'], 'Les équipements sans modèle ne doivent pas être fusionnés arbitrairement.');
$check($groups[4]['name'] === 'Sans modèle', 'Un ancien équipement sans modèle doit conserver son nom complet.');
$vehicles = [
    ['id' => 10, 'name' => 'Zoe 01', 'vehicle_brand' => 'Renault', 'vehicle_model' => 'Zoe', 'vehicle_class' => 'citadine', 'fuel_type' => 'electrique', 'places' => 4, 'location' => 'Parking A'],
    ['id' => 11, 'name' => 'Zoe 02', 'vehicle_brand' => 'Renault', 'vehicle_model' => 'Zoe', 'vehicle_class' => 'citadine', 'fuel_type' => 'electrique', 'places' => 4, 'location' => 'Parking B'],
    ['id' => 12, 'name' => 'Zoe 03', 'vehicle_brand' => 'Renault', 'vehicle_model' => 'Zoe', 'vehicle_class' => 'citadine', 'fuel_type' => 'electrique', 'places' => 5, 'location' => 'Parking B'],
];
$vehicleGroups = $groupResources->invokeArgs($controller, [&$vehicles, true]);
$check(count($vehicleGroups) === 2 && $vehicleGroups[0]['count'] === 2, 'Les critères de regroupement des véhicules doivent rester inchangés.');
echo "7 contrôles de regroupement réussis.\n";