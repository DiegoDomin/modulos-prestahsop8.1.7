<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class LiveOrderStatus extends Module
{
    public function __construct()
    {
        $this->name = 'liveorderstatus';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0'; // Incrementamos la versión para reflejar la mejora
        $this->author = 'DIEGO DOMINGUEZ';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Live Order Status');
        $this->description = $this->l('Permite a los clientes ver el estado de sus pedidos en tiempo real.');
    }
    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayAdminOrder') &&
            $this->registerHook('displayOrderConfirmation') && // Hook para el botón "Ver estado de pedido"
            $this->registerHook('moduleRoutes') && // Hook para rutas personalizadas
            $this->installConfiguration() &&
            $this->createDatabaseTable(); // Crear la tabla al instalar

    }

    public function uninstall()
    {
        return parent::uninstall() &&
            Configuration::deleteByName('GOOGLE_MAPS_API_KEY') &&
            $this->dropDatabaseTable(); // Eliminar la tabla al desinstalar

    }

    private function createDatabaseTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "delivery_tracking` (
            `id_tracking` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_order` INT UNSIGNED NOT NULL,
            `id_employee` INT UNSIGNED NOT NULL,
            `latitude` VARCHAR(50) NOT NULL,
            `longitude` VARCHAR(50) NOT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_tracking`),
            UNIQUE KEY `unique_order_employee` (`id_order`, `id_employee`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4;";

        return Db::getInstance()->execute($sql);
    }

    private function dropDatabaseTable()
    {
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "delivery_tracking`;";
        return Db::getInstance()->execute($sql);
    }
    public function hookDisplayOrderConfirmation($params)
    {
        $order = $params['order'];

        // Asegurarse de que el pedido existe antes de procesarlo
        if (!$order || !Validate::isLoadedObject($order)) {
            return '';
        }

        $this->context->smarty->assign([
            'order_id' => $order->id,
            'status_url' => $this->context->link->getModuleLink($this->name, 'status', [
                'order_id' => $order->id
            ]),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/display_order_confirmation.tpl');
    }
    private function installConfiguration()
    {
        Configuration::updateValue('GOOGLE_MAPS_API_KEY', '');
        return true;
    }
    public function hookModuleRoutes()
    {
        return [
            'module-liveorderstatus-status' => [
                'controller' => 'status',
                'rule' => 'order-status/{order_id}',
                'keywords' => [
                    'order_id' => ['regexp' => '[0-9]+', 'param' => 'order_id']
                ],
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name
                ]
            ],
            'module-liveorderstatus-checkstatus' => [
                'controller' => 'checkstatus',
                'rule' => 'check-status/{order_id}',
                'keywords' => [
                    'order_id' => ['regexp' => '[0-9]+', 'param' => 'order_id']
                ],
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name
                ]
                ],
                'module-liveorderstatus-updatedeliverylocation' => [
                    'controller' => 'updatedeliverylocation',
                    'rule' => 'order-status/{order_id}/delivery-location',
                    'keywords' => [
                        'order_id' => ['regexp' => '[0-9]+', 'param' => 'order_id'],
                    ],
                    'params' => [
                        'fc' => 'module',
                        'module' => $this->name,
                    ],
                ],
        ];
    }


    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitGoogleMapsAPIKey')) {
            $apiKey = Tools::getValue('GOOGLE_MAPS_API_KEY');
    
            if (!empty($apiKey)) {
                Configuration::updateValue('GOOGLE_MAPS_API_KEY', $apiKey);
                $output .= $this->displayConfirmation($this->l('Google Maps API Key updated successfully.'));
            } else {
                $output .= $this->displayError($this->l('Google Maps API Key cannot be empty.'));
            }
        }
    
        return $output . $this->renderForm();
    }
    
    


     private function renderForm()
    {
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Google Maps API Configuration'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Google Maps API Key'),
                    'name' => 'GOOGLE_MAPS_API_KEY',
                    'size' => 60,
                    'required' => true,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
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
        $helper->submit_action = 'submitGoogleMapsAPIKey';
        $helper->fields_value['GOOGLE_MAPS_API_KEY'] = Configuration::get('GOOGLE_MAPS_API_KEY');
   // Obtener el valor actual de la clave desde la configuración
   $apiKey = Configuration::get('GOOGLE_MAPS_API_KEY');
   $helper->fields_value['GOOGLE_MAPS_API_KEY'] = $apiKey;
   
        return $helper->generateForm($fieldsForm);
    }

    public function hookDisplayBackOfficeHeader($params)
    {
        $apiKey = Configuration::get('GOOGLE_MAPS_API_KEY');
    
        if (!empty($apiKey)) {
            // Añadir JS para Google Maps
            $this->context->controller->addJS($this->_path . 'views/js/admin_google_maps.js');
        } else {
            $this->context->controller->warnings[] = $this->l('Google Maps API Key is missing. Please enter a valid key to enable map functionality.');
        }
    }
    

    public function hookDisplayAdminOrder($params)
    {
        $orderId = (int)$params['id_order'];
    
        // Consulta SQL dinámica para obtener los datos necesarios
        $sql = new DbQuery();
        $sql->select('
            o.id_order,
            a.need_tax_invoice,
            a.razon_social,
            a.nit_number,
            a.latitude,
            a.longitude
        ')
        ->from('orders', 'o') // Tabla de pedidos (ps_orders o tu prefijo psqq_orders)
        ->innerJoin('address', 'a', 'a.id_address = o.id_address_delivery') // Unir con ps_address
        ->where('o.id_order = ' . $orderId);
    
        // Ejecutar la consulta
        $result = Db::getInstance()->getRow($sql);
    
        // Asignar valores obtenidos o valores predeterminados
        $latitude = $result['latitude'] ?? null;
        $longitude = $result['longitude'] ?? null;
    
        // Generar el token dinámico
        $adminToken = Tools::getAdminTokenLite('AdminUpdateDeliveryLocation');
    
        // Construir el enlace del botón
        $link = Context::getContext()->link->getAdminLink('AdminLiveOrderStatus');
        if ($latitude && $longitude) {
            $link .= '&id_order=' . $orderId . '&lat=' . $latitude . '&lng=' . $longitude;
        } else {
            $link .= '&id_order=' . $orderId;
        }
    
        // HTML del botón
        $buttonHtml = '
            <a href="' . $link . '" class="btn btn-primary" id="trackOrderButton" style="margin-top: 10px;">
                ' . $this->l('Empezar Seguimiento De Pedido') . '
            </a>
        ';
    
        // Agregar el JavaScript para mover el botón
        $js = '
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const button = document.getElementById("trackOrderButton");
                const orderActions = document.querySelector("div.order-actions");
                const printForm = document.querySelector("form.order-actions-print");
    
                if (button && orderActions && printForm) {
                    printForm.insertAdjacentElement("afterend", button);
                } else {
                    console.error("No se encontraron los elementos necesarios para reubicar el botón de seguimiento.");
                }
            });
        </script>
        ';
    
        // Devolver el HTML del botón + JavaScript
        return $buttonHtml . $js;
    }
    

    
    

    
    
    
}