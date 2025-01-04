<?php

class LiveOrderStatusStatusModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $orderId = (int)Tools::getValue('order_id');

        // Verificar si el pedido existe y pertenece al cliente actual
        $order = new Order($orderId);
        if (!$order->id || $order->id_customer != $this->context->customer->id) {
            Tools::redirect('index.php?controller=history');
        }

        // Obtener el estado actual del pedido
        $currentState = $order->getCurrentOrderState();

        // Obtener coordenadas del pedido desde la tabla de pedidos
        $sql = new DbQuery();
        $sql->select('latitude, longitude')
        ->from('delivery_tracking')
        ->where('id_order = ' . (int)$orderId)
        ->orderBy('date_add DESC'); // Tomar la última entrada registrada
        $result = Db::getInstance()->getRow($sql);
        $latitude = isset($result['latitude']) ? $result['latitude'] : null;
        $longitude = isset($result['longitude']) ? $result['longitude'] : null;

        // Lógica para decidir si mostrar el mapa (opcional según el estado del pedido)
        $showMap = $latitude && $longitude && in_array($currentState->name[$this->context->language->id], ['Enviado', 'Preparación en curso']);

        // Asignar variables a Smarty
        $this->context->smarty->assign([
            'order_id' => $orderId,
            'order_reference' => $order->reference,
            'order_state' => isset($currentState->name[$this->context->language->id])
                ? $currentState->name[$this->context->language->id]
                : $this->module->l('Unknown state'),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'show_map' => $showMap,
            'googleMapsApiKey' => Configuration::get('GOOGLE_MAPS_API_KEY'),
        ]);

        // Configurar la plantilla para mostrar el estado y el mapa
        $this->setTemplate('module:liveorderstatus/views/templates/front/status.tpl');
    }
}
