<?php
class DistanceBasedShippingCalculateCostModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // Verificar si los datos han sido enviados correctamente
        $jsonInput = Tools::file_get_contents('php://input');
        $inputData = json_decode($jsonInput, true);

        // Validar la entrada
        if (!isset($inputData['latitude'], $inputData['longitude'])) {
            header('Content-Type: application/json');
            die(json_encode([
                'success' => false,
                'message' => 'Coordenadas no enviadas correctamente.'
            ]));
        }

        $latitude = $inputData['latitude'];
        $longitude = $inputData['longitude'];

        // Obtener configuraciones
        $apiKey = Configuration::get('DBS_API_KEY');
        $costPerKm = Configuration::get('DBS_COST_PER_KM');
        $storeLatitude = Configuration::get('DBS_STORE_LATITUDE');
        $storeLongitude = Configuration::get('DBS_STORE_LONGITUDE');

        if (!$apiKey || !$costPerKm || !$storeLatitude || !$storeLongitude) {
            header('Content-Type: application/json');
            die(json_encode([
                'success' => false,
                'message' => 'Configuraciones no establecidas en el BackOffice.'
            ]));
        }

        // Consultar la API de Google Distance Matrix
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$storeLatitude},{$storeLongitude}&destinations={$latitude},{$longitude}&key={$apiKey}";
        $response = Tools::file_get_contents($url);
        $data = json_decode($response, true);

        if (!isset($data['rows'][0]['elements'][0]['distance']['value'])) {
            header('Content-Type: application/json');
            die(json_encode([
                'success' => false,
                'message' => 'No se pudo calcular la distancia.'
            ]));
        }

        // Calcular distancia y costo
        $distanceMeters = $data['rows'][0]['elements'][0]['distance']['value'];
        $distanceKm = $distanceMeters / 1000;
        $additionalCost = $distanceKm * $costPerKm;

        // Guardar el costo adicional en la sesión del carrito
        $this->context->cart->setAdditionalShippingCost($additionalCost);

        // Responder con éxito
        header('Content-Type: application/json');
        die(json_encode([
            'success' => true,
            'additional_cost' => $additionalCost
        ]));
    }
}
