<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class CamposBackOffice extends Module
{
    public function __construct()
    {
        $this->name = 'camposbackoffice';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Tu Nombre';
        parent::__construct();

        $this->displayName = $this->l('Módulo Campos Personalizados');
        $this->description = $this->l('Muestra campos extra de la dirección en la ficha de pedido. En EL BACKOFFICE.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayAdminOrderMain')
            && $this->registerHook('actionValidateOrder')
            && $this->createDatabaseTable();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->deleteDatabaseTable();
    }
    private function createDatabaseTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'order_payment_details` (
            `id_order_payment_details` INT(11) NOT NULL AUTO_INCREMENT,
            `id_order` INT(11) NOT NULL,
            `cash_given` DECIMAL(10,2) NOT NULL,
            `change` DECIMAL(10,2) NOT NULL,
            PRIMARY KEY (`id_order_payment_details`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
        return Db::getInstance()->execute($sql);
    }
    private function deleteDatabaseTable()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'order_payment_details`';
        return Db::getInstance()->execute($sql);
    }
    /**
     * Hook que se ejecuta en la vista detalle de un pedido en BackOffice
     */
    public function hookDisplayAdminOrderMain($params)
    {
        $idOrder = (int) $params['id_order'];
        $order = new Order($idOrder);

        // 2) Obtener la dirección de facturación
        $idAddressInvoice = (int) $order->id_address_invoice;
        $addressInvoice   = new Address($idAddressInvoice);

        // 3) Consultar los datos de recogida del carrito
        $idCart = (int) $order->id_cart;

        // Consulta para el horario seleccionado
        $pickupData = Db::getInstance()->getRow(
            'SELECT pickup_day, pickup_time FROM ' . _DB_PREFIX_ . 'cart_pickup_selection WHERE id_cart = ' . $idCart
        );
        $paymentDetails = Db::getInstance()->getRow(
            'SELECT cash_given, change 
             FROM ' . _DB_PREFIX_ . 'order_payment_details 
             WHERE id_order = ' . (int)$idOrder
        );

        // Consulta para la opción de empaque seleccionada
        $packagingData = Db::getInstance()->getRow(
            'SELECT pod.title 
             FROM ' . _DB_PREFIX_ . 'packaging_options po
             INNER JOIN ' . _DB_PREFIX_ . 'packaging_options_data pod 
             ON po.packaging_option = pod.id_option
             WHERE po.id_cart = ' . $idCart
        );
        // Asignar variables a Smarty
        $this->context->smarty->assign([
            'need_tax_invoice'   => $addressInvoice->need_tax_invoice,
            'razon_social'       => $addressInvoice->razon_social,
            'nit_number'         => $addressInvoice->nit_number,
            'latitude'           => $addressInvoice->latitude,
            'longitude'          => $addressInvoice->longitude,
            'pickup_day'         => $pickupData['pickup_day'] ?? 'No seleccionado',
            'pickup_time'        => $pickupData['pickup_time'] ?? 'No seleccionado',
            'packaging_option'   => $packagingData['title'] ?? 'No seleccionado',
            'cash_given' => $paymentDetails['cash_given'] ?? 'No especificado',
            'change' => $paymentDetails['change'] ?? 'No especificado',
        ]);

            // 4) Agregamos JavaScript para mover el bloque debajo de #privateNote
    $js = '
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const extraDetails = document.getElementById("extraOrderDetails");
            const privateNote = document.getElementById("privateNote");
            if (extraDetails && privateNote) {
                privateNote.insertAdjacentElement("afterend", extraDetails);
            }
        });
    </script>
';
        // 4) Renderizamos
        return $this->display(__FILE__, 'views/templates/hook/order_custom_fields.tpl') . $js;
    }
    

    public function hookActionValidateOrder($params)
{
    $order = $params['order'];

    $cashGiven = Tools::getValue('cash_given');
    $change = Tools::getValue('change');

    if ($cashGiven !== null && $change !== null) {
        Db::getInstance()->insert('order_payment_details', [
            'id_order' => (int)$order->id,
            'cash_given' => (float)$cashGiven,
            'change' => (float)$change,
        ]);
    }
}

}
