<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';

header('Content-Type: application/json');

$dbPrefix = _DB_PREFIX_; // Obtener el prefijo configurado en PrestaShop.

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
try {
    $zones = Db::getInstance()->executeS('SELECT name, polygon_coordinates, polygon_color, polygon_fill FROM `' . $dbPrefix . 'restricted_zones`');


    if ($zones === false) {
        throw new Exception('Error en la consulta: ' . Db::getInstance()->getMsgError());
    }

    // Limpiar y validar JSON
    foreach ($zones as &$zone) {
        $zone['polygon_coordinates'] = json_decode($zone['polygon_coordinates'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Coordenadas inválidas para la zona: ' . $zone['name']);
        }
        $zone['polygon_color'] = $zone['polygon_color'] ?? '#FF0000';
        $zone['polygon_fill'] = $zone['polygon_fill'] ?? 1;
        $zone['polygon_coordinates'] = json_encode($zone['polygon_coordinates']); // Volver a JSON limpio
    }

    echo json_encode(['zones' => $zones]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Excepción capturada: ' . $e->getMessage()]);
}

    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['lat']) || !isset($data['lng'])) {
        echo json_encode(['error' => 'Parametros faltantes: lat y lng.']);
        exit;
    }

    $lat = $data['lat'];
    $lng = $data['lng'];

    try {
        $zones = Db::getInstance()->executeS('SELECT polygon_coordinates FROM `' . $dbPrefix . 'restricted_zones`');

        if ($zones === false) {
            throw new Exception('Error en la consulta: ' . Db::getInstance()->getMsgError());
        }

        $restricted = false;

        foreach ($zones as $zone) {
            $polygon = json_decode($zone['polygon_coordinates'], true);

            if (is_array($polygon) && pointInPolygon($lat, $lng, $polygon)) {
                $restricted = true;
                break;
            }
        }

        echo json_encode(['restricted' => $restricted]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Excepci贸n capturada: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request method.']);
exit;

function pointInPolygon($lat, $lng, $polygon) {
    $inside = false;
    $x = $lat;
    $y = $lng;
    $n = count($polygon);

    for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
        $xi = $polygon[$i]['lat'];
        $yi = $polygon[$i]['lng'];
        $xj = $polygon[$j]['lat'];
        $yj = $polygon[$j]['lng'];

        $intersect = (($yi > $y) != ($yj > $y)) &&
                     ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);
        if ($intersect) {
            $inside = !$inside;
        }
    }

    return $inside;
}
?>
