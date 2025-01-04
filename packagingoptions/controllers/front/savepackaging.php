<?php
class PackagingOptionsSavePackagingModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if (Tools::isSubmit('ajax')) {
            $packagingOption = Tools::getValue('packaging_option');
            $cartId = $this->context->cart->id;

            // Guarda la opción seleccionada en la base de datos
            $exists = Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'packaging_options` WHERE `id_cart` = ' . (int)$cartId);

            if ($exists) {
                Db::getInstance()->update(
                    'packaging_options',
                    ['packaging_option' => pSQL($packagingOption)],
                    'id_cart = ' . (int)$cartId
                );
            } else {
                Db::getInstance()->insert(
                    'packaging_options',
                    [
                        'id_cart' => (int)$cartId,
                        'packaging_option' => pSQL($packagingOption),
                    ]
                );
            }

            die(json_encode(['success' => true]));
        }

        Tools::redirect('index.php');
    }
}
