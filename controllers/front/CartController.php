<?php
class CartController extends CartControllerCore
{
    public function initContent()
    {
        parent::initContent();

        // Asigna el costo adicional de envío al contexto de Smarty
        $cart = $this->context->cart;
        $additionalShippingCost = isset($cart->additional_shipping_cost) ? $cart->additional_shipping_cost : 0;

$this->context->smarty->assign('additional_shipping_cost', (float) ($cart->additional_shipping_cost ?? 0));
    }
}
