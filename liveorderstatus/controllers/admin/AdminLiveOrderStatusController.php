<?php

class AdminLiveOrderStatusController extends AdminController
{
    public $module;

    public function __construct()
    {
        parent::__construct();

        // Instanciar el módulo manualmente
        $this->module = Module::getInstanceByName('liveorderstatus');
    }

    public function initContent()
    {
        parent::initContent();
    
        $this->content = $this->renderContent();
        parent::initContent();
    }

    public function renderContent()
    {
        // Obtener el ID del pedido
        $orderId = (int)Tools::getValue('id_order');

        if (!$orderId) {
            $this->errors[] = $this->trans('Order ID is missing.', [], 'Admin.Orderscustomers.Notification');
            return;
        }

        // Consultar las coordenadas desde la base de datos
        $latitude = null;
        $longitude = null;

        if ($orderId) {
            $order = new Order($orderId);
            if (!Validate::isLoadedObject($order)) {
                $this->errors[] = $this->trans('Order not found.', [], 'Admin.Orderscustomers.Notification');
                return;
            }

            // Obtener la dirección de entrega
            $address = new Address($order->id_address_delivery);
            $latitude = $address->latitude ?? '13.669526';
            $longitude = $address->longitude ?? '-89.228413';

            // Obtener los estados del pedido y el estado actual
            $statuses = OrderState::getOrderStates((int)$this->context->language->id);
            $currentStatus = $order->current_state;

            // Generar token para actualización de estado
            $adminTokenDelivery = Tools::getAdminTokenLite('AdminUpdateDeliveryLocation');
            $adminTokenStatus = Tools::getAdminTokenLite('AdminUpdateOrderStatus');            $employeeId = $this->context->employee->id;

            // Asignar variables a Smarty
            $this->context->smarty->assign([
                'employee_id' => $employeeId,
                'order_id' => $orderId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'googleMapsApiKey' => Configuration::get('GOOGLE_MAPS_API_KEY'),
                'statuses' => $statuses,
                'current_status' => $currentStatus,
                'adminTokenDelivery' => $adminTokenDelivery,
                'adminTokenStatus' => $adminTokenStatus,
                        ]);
        }

        // Renderizar la plantilla
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/live_order_map.tpl');
    }
}
