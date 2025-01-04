<?php

class AdminUpdateDeliveryLocationController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        // 1. Obtenemos el ID del empleado logueado
        $employeeId = (int)$this->context->employee->id;

        // 2. Recogemos parámetros lat/long/order_id vía POST
        $latitude = Tools::getValue('latitude');
        $longitude = Tools::getValue('longitude');
        $orderId = (int)Tools::getValue('order_id');

        // 3. Validamos datos
        if ($latitude && $longitude && $orderId) {
            // 4. Insertamos en la tabla ps_delivery_tracking
            $insertData = [
                'id_order'    => $orderId,
                'id_employee' => $employeeId,
                'latitude'    => pSQL($latitude),
                'longitude'   => pSQL($longitude),
                'date_add'    => date('Y-m-d H:i:s'),
            ];

            $res = Db::getInstance()->execute('
            INSERT INTO psqq_delivery_tracking (id_order, id_employee, latitude, longitude, date_add)
            VALUES (' . (int)$orderId . ', ' . (int)$employeeId . ', "' . pSQL($latitude) . '", "' . pSQL($longitude) . '", "' . date('Y-m-d H:i:s') . '")
            ON DUPLICATE KEY UPDATE
                latitude = VALUES(latitude),
                longitude = VALUES(longitude),
                date_add = VALUES(date_add)
        ');
        
            if ($res) {
                die(json_encode([
                    'success' => true,
                    'employee_id' => $employeeId,
                    'message' => 'Coordinates saved in delivery_tracking'
                ]));
            } else {
                die(json_encode([
                    'success' => false,
                    'message' => 'Database insert failed'
                ]));
            }
        }

        // Si faltan datos o algo falló
        die(json_encode([
            'success' => false,
            'message' => 'Invalid data'
        ]));
    }
}
