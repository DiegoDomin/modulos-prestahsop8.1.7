<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class DistanceBasedShipping extends Module
{
    public function __construct()
    {
        $this->name = 'distancebasedshipping';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Diego';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Distance Based Shipping');
        $this->description = $this->l('Calcula el costo de envío en función de la distancia utilizando la API de Google Distance Matrix.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionCarrierProcess')
            && $this->registerHook('displayAdminOrder')
            && Configuration::updateValue('DBS_API_KEY', '')
            && Configuration::updateValue('DBS_COST_PER_KM', 0)
            && Configuration::updateValue('DBS_STORE_LATITUDE', '')
            && Configuration::updateValue('DBS_STORE_LONGITUDE', '');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('DBS_API_KEY')
            && Configuration::deleteByName('DBS_COST_PER_KM')
            && Configuration::deleteByName('DBS_STORE_LATITUDE')
            && Configuration::deleteByName('DBS_STORE_LONGITUDE');
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit' . $this->name)) {
            $apiKey = Tools::getValue('DBS_API_KEY');
            $costPerKm = (float)Tools::getValue('DBS_COST_PER_KM');
            $storeLatitude = Tools::getValue('DBS_STORE_LATITUDE');
            $storeLongitude = Tools::getValue('DBS_STORE_LONGITUDE');

            Configuration::updateValue('DBS_API_KEY', $apiKey);
            Configuration::updateValue('DBS_COST_PER_KM', $costPerKm);
            Configuration::updateValue('DBS_STORE_LATITUDE', $storeLatitude);
            Configuration::updateValue('DBS_STORE_LONGITUDE', $storeLongitude);

            $output .= $this->displayConfirmation($this->l('Configuración actualizada'));
        }

        return $output . $this->renderForm();
    }

    public function renderForm()
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuración de Envío Basado en Distancia'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Clave API de Google'),
                        'name' => 'DBS_API_KEY',
                        'required' => true,
                        'desc' => $this->l('Introduce tu clave de la API de Google Distance Matrix.'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Costo por kilómetro'),
                        'name' => 'DBS_COST_PER_KM',
                        'required' => true,
                        'desc' => $this->l('Costo adicional por cada kilómetro recorrido.'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Latitud de la tienda'),
                        'name' => 'DBS_STORE_LATITUDE',
                        'required' => true,
                        'desc' => $this->l('Introduce la latitud de tu tienda.'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Longitud de la tienda'),
                        'name' => 'DBS_STORE_LONGITUDE',
                        'required' => true,
                        'desc' => $this->l('Introduce la longitud de tu tienda.'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Guardar'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submit' . $this->name;

        $helper->fields_value['DBS_API_KEY'] = Configuration::get('DBS_API_KEY');
        $helper->fields_value['DBS_COST_PER_KM'] = Configuration::get('DBS_COST_PER_KM');
        $helper->fields_value['DBS_STORE_LATITUDE'] = Configuration::get('DBS_STORE_LATITUDE');
        $helper->fields_value['DBS_STORE_LONGITUDE'] = Configuration::get('DBS_STORE_LONGITUDE');

        return $helper->generateForm([$fieldsForm]);
    }

    public function hookActionCarrierProcess($params)
    {
        // Verifica que los parámetros contengan el carrito
        if (!isset($params['cart']) || !$params['cart'] instanceof Cart) {
            error_log('El carrito no está definido o no es una instancia válida.');
            return;
        }
    
        // Obtener el carrito
        $cart = $params['cart'];
    
        // Verificar la dirección del cliente
        if (!isset($cart->id_address_delivery) || !$cart->id_address_delivery) {
            error_log('No se encontró una dirección de entrega asociada al carrito.');
            return;
        }
    
        $address = new Address($cart->id_address_delivery);
    
        // Verifica que las coordenadas estén disponibles en la dirección
        if (empty($address->latitude) || empty($address->longitude)) {
            error_log('Las coordenadas de la dirección no están definidas.');
            return;
        }
    
        $customerLat = $address->latitude;
        $customerLong = $address->longitude;
    
        // Verifica que las configuraciones estén disponibles
        $apiKey = Configuration::get('DBS_API_KEY');
        $costPerKm = Configuration::get('DBS_COST_PER_KM');
        $storeLatitude = Configuration::get('DBS_STORE_LATITUDE');
        $storeLongitude = Configuration::get('DBS_STORE_LONGITUDE');
    
        if (!$apiKey || !$costPerKm || !$storeLatitude || !$storeLongitude) {
            error_log('Las configuraciones no están completas.');
            return;
        }
    
        // Consulta la API de Google Distance Matrix
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$storeLatitude},{$storeLongitude}&destinations={$customerLat},{$customerLong}&key={$apiKey}";
        $response = Tools::file_get_contents($url);
        $data = json_decode($response, true);
    
        if (!isset($data['rows'][0]['elements'][0]['distance']['value'])) {
            error_log('La API de Google no devolvió resultados válidos. Respuesta: ' . $response);
            return;
        }
    
        $distanceMeters = $data['rows'][0]['elements'][0]['distance']['value'];
        $distanceKm = $distanceMeters / 1000;
    
        // Calcular costo adicional
        $additionalCost = $distanceKm * $costPerKm;
    
        // Agregar el costo adicional al carrito
        $cart->additional_shipping_cost = $additionalCost;
        $cart->update(); // Asegúrate de guardar el cambio en la base de datos
    }
    
    public function hookDisplayAdminOrder($params)
{
    // Verificar que los parámetros contengan un ID de pedido válido
    if (!isset($params['id_order']) || !$params['id_order']) {
        return '';
    }

    // Obtener el ID del pedido
    $orderId = (int)$params['id_order'];

    // Obtener la dirección asociada al pedido
    $order = new Order($orderId);
    $address = new Address($order->id_address_delivery);

    // Obtener las coordenadas de la dirección del cliente
    $latitude = $address->latitude;
    $longitude = $address->longitude;

    // Verificar si las coordenadas están disponibles
    if (!$latitude || !$longitude) {
        return '<p>' . $this->l('No hay coordenadas disponibles para esta dirección.') . '</p>';
    }

    // Calcular el costo adicional basado en la distancia
    $apiKey = Configuration::get('DBS_API_KEY');
    $costPerKm = Configuration::get('DBS_COST_PER_KM');
    $storeLatitude = Configuration::get('DBS_STORE_LATITUDE');
    $storeLongitude = Configuration::get('DBS_STORE_LONGITUDE');

    if (!$apiKey || !$costPerKm || !$storeLatitude || !$storeLongitude) {
        return '<p>' . $this->l('La configuración del módulo no está completa.') . '</p>';
    }

    // Consultar la API de Google Distance Matrix
    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$storeLatitude},{$storeLongitude}&destinations={$latitude},{$longitude}&key={$apiKey}";
    $response = Tools::file_get_contents($url);
    $data = json_decode($response, true);

    if (!isset($data['rows'][0]['elements'][0]['distance']['value'])) {
        return '<p>' . $this->l('No se pudo calcular la distancia.') . '</p>';
    }

    // Calcular distancia y costo
    $distanceMeters = $data['rows'][0]['elements'][0]['distance']['value'];
    $distanceKm = $distanceMeters / 1000;
    $additionalCost = $distanceKm * $costPerKm;

    // Mostrar la distancia y el costo adicional en el BackOffice
    return '<p>' . $this->l('Distancia calculada: ') . number_format($distanceKm, 2) . ' km</p>'
        . '<p>' . $this->l('Costo adicional: ') . Tools::displayPrice($additionalCost) . '</p>';
}

}
