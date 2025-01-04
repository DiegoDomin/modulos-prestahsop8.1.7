<?php
class LiveOrderStatusCheckStatusModuleFrontController extends ModuleFrontController
{
    public function display()
    {
        $orderId = (int)Tools::getValue('order_id');
        $order = new Order($orderId);

        // Verifica que el pedido exista y pertenezca al cliente
        if (!$order->id || $order->id_customer != $this->context->customer->id) {
            die(json_encode(['error' => true, 'message' => 'Pedido no encontrado o no pertenece al cliente.']));
        }

        // Obtiene el estado actual del pedido
        $currentState = $order->getCurrentOrderState();

        if (!$currentState) {
            die(json_encode([
                'error' => true,
                'message' => 'El estado del pedido no está definido.',
            ]));
        }

        $orderStateName = 'Estado desconocido';

        if (isset($currentState->name[$this->context->language->id])) {
            $orderStateName = $currentState->name[$this->context->language->id];
        }

        die(json_encode([
            'order_state' => $orderStateName,
        ]));
    }
}
