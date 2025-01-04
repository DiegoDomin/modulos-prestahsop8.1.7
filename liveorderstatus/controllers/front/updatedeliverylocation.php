<?php

class LiveOrderStatusUpdateDeliveryLocationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // Encabezado JSON
        header('Content-Type: application/json');

        try {
            // Validar el ID del pedido
            $orderId = Tools::getValue('order_id');
            if (!$orderId || !is_numeric($orderId)) {
                throw new Exception('ID del pedido no válido.');
            }

            $db = Db::getInstance();

            // Obtener coordenadas del delivery desde `psqq_delivery_tracking`
            $queryDelivery = new DbQuery();
            $queryDelivery->select('latitude as delivery_latitude, longitude as delivery_longitude')
                ->from('delivery_tracking') // Asegúrate de usar el prefijo completo de la tabla
                ->where('id_order = ' . (int)$orderId)
                ->orderBy('date_add DESC'); // Tomar la última entrada registrada

            $deliveryResult = $db->getRow($queryDelivery);

            if (!$deliveryResult) {
                throw new Exception('Coordenadas del delivery no disponibles.');
            }

            // Obtener coordenadas del cliente desde `psqq_address`
            $queryCustomer = new DbQuery();
            $queryCustomer->select('a.latitude as customer_latitude, a.longitude as customer_longitude')
                ->from('address', 'a') // Asegúrate de usar el prefijo completo de la tabla
                ->innerJoin('orders', 'o', 'a.id_address = o.id_address_delivery')
                ->where('o.id_order = ' . (int)$orderId);

            $customerResult = $db->getRow($queryCustomer);

            if (!$customerResult) {
                throw new Exception('Coordenadas del cliente no disponibles.');
            }

            // Respuesta exitosa
            echo json_encode([
                'success' => true,
                'delivery_latitude' => $deliveryResult['delivery_latitude'],
                'delivery_longitude' => $deliveryResult['delivery_longitude'],
                'customer_latitude' => $customerResult['customer_latitude'],
                'customer_longitude' => $customerResult['customer_longitude'],
            ]);
        } catch (Exception $e) {
            // Manejar errores y asegurarse de que se devuelve JSON
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        exit;
    }
}
