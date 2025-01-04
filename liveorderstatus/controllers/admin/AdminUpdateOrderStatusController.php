<?php

class AdminUpdateOrderStatusController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function ajaxProcessUpdateOrderStatus()
    {
        header('Content-Type: application/json');

        // Validar parámetros
        $orderId = (int)Tools::getValue('id_order');
        $newStatus = (int)Tools::getValue('new_status');

        if (!$orderId || !$newStatus) {
            die(json_encode([
                'success' => false,
                'message' => 'Invalid parameters.',
            ]));
        }

        // Validar si el pedido existe
        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            die(json_encode([
                'success' => false,
                'message' => 'Order not found.',
            ]));
        }

        try {
            // Cambiar el estado del pedido
            $history = new OrderHistory();
            $history->id_order = $order->id;
            $history->changeIdOrderState($newStatus, $orderId);
            $history->add();

            die(json_encode([
                'success' => true,
                'message' => 'Order status updated successfully.',
            ]));
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }
    }
}
