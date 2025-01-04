<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class horariosBackOffice extends Module
{
    public function __construct()
    {
        $this->name = 'horariosBackOffice';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'diego';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Opciones dinámicas de recogida');
        $this->description = $this->l('Permite gestionar días y horarios de recogida en tienda desde el Back Office.');
        $this->ps_versions_compliancy = ['min' => '1.7.7', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayBeforeCarrier')
            && $this->registerHook('actionFrontControllerSetMedia') // Registramos el nuevo hook
            && $this->createConfigTable() 
            && $this->createCartPickupTable()
            && $this->createReminderTable();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->deleteConfigTable()
            && $this->deleteCartPickupTable()
            && $this->deleteReminderTable();

    }

    private function createConfigTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "pickup_options` (
            `id_pickup_option` INT(11) NOT NULL AUTO_INCREMENT,
            `carrier_name` VARCHAR(255) NOT NULL,
            `schedule` LONGTEXT NOT NULL, -- JSON con días y horarios
            PRIMARY KEY (`id_pickup_option`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
        return Db::getInstance()->execute($sql);
    }
    
    
    private function createCartPickupTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cart_pickup_selection` (
            `id_cart_pickup_selection` INT(11) NOT NULL AUTO_INCREMENT,
            `id_cart` INT(11) NOT NULL,
            `pickup_day` VARCHAR(255) NOT NULL,
            `pickup_time` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id_cart_pickup_selection`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
        
        return Db::getInstance()->execute($sql);
    }
    private function createReminderTable()
{
    $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "pickup_reminders` (
        `id_reminder` INT(11) NOT NULL AUTO_INCREMENT,
        `id_order` INT(11) NOT NULL,
        `id_customer` INT(11) NOT NULL,
        `reminder_time` DATETIME NOT NULL,
        `notification_hours_before` INT(11) NOT NULL DEFAULT 0,
        `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
        PRIMARY KEY (`id_reminder`)
    ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
    return Db::getInstance()->execute($sql);
}

private function deleteReminderTable()
{
    $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "pickup_reminders`;";
    return Db::getInstance()->execute($sql);
}

    private function deleteConfigTable()
    {
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "pickup_options`;";
        return Db::getInstance()->execute($sql);
    }
    private function deleteCartPickupTable()
    {
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "cart_pickup_selection`;";
        return Db::getInstance()->execute($sql);
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('sendPickupReminder')) {
            $idOrder = (int) Tools::getValue('id_order');
        
            if ($idOrder) {
                $reminder = Db::getInstance()->getRow("SELECT 
                        r.reminder_time, 
                        r.id_customer, 
                        c.email, 
                        cps.pickup_time 
            FROM " . _DB_PREFIX_ . "pickup_reminders r
            INNER JOIN " . _DB_PREFIX_ . "customer c ON r.id_customer = c.id_customer
            INNER JOIN " . _DB_PREFIX_ . "orders o ON r.id_order = o.id_order
            INNER JOIN " . _DB_PREFIX_ . "cart_pickup_selection cps ON o.id_cart = cps.id_cart
            WHERE r.id_order = " . (int)$idOrder
                );
        
                if ($reminder) {
                    $pickupTime = $reminder['pickup_time'];
                    $email = $reminder['email'];
                    $reminderTime = $reminder['reminder_time'];
                    $customerName = Db::getInstance()->getValue("
                    SELECT CONCAT(firstname, ' ', lastname) AS name 
                    FROM " . _DB_PREFIX_ . "customer 
                    WHERE id_customer = " . (int)$reminder['id_customer']
                );

            // Formatear la fecha del recordatorio
            $formattedDate = date('l j F Y', strtotime($reminderTime));
            $diasSemana = [
                'Monday' => 'Lunes',
                'Tuesday' => 'Martes',
                'Wednesday' => 'Miércoles',
                'Thursday' => 'Jueves',
                'Friday' => 'Viernes',
                'Saturday' => 'Sábado',
                'Sunday' => 'Domingo'
            ];
            $meses = [
                'January' => 'enero',
                'February' => 'febrero',
                'March' => 'marzo',
                'April' => 'abril',
                'May' => 'mayo',
                'June' => 'junio',
                'July' => 'julio',
                'August' => 'agosto',
                'September' => 'septiembre',
                'October' => 'octubre',
                'November' => 'noviembre',
                'December' => 'diciembre'
            ];
            $formattedDate = strtr($formattedDate, $diasSemana);
            $formattedDate = strtr($formattedDate, $meses);
            $pickupHour = explode(' - ', $pickupTime)[0];

    // Obtener productos del pedido y total
    $orderDetails = Db::getInstance()->executeS("
    SELECT 
        pl.name AS product_name, 
        od.product_quantity, 
        od.total_price_tax_incl 
    FROM " . _DB_PREFIX_ . "order_detail od
    INNER JOIN " . _DB_PREFIX_ . "product_lang pl ON od.product_id = pl.id_product AND pl.id_lang = " . (int)$this->context->language->id . "
    WHERE od.id_order = " . (int)$idOrder
);

    
    $orderTotal = Db::getInstance()->getValue("
        SELECT total_paid_tax_incl 
        FROM " . _DB_PREFIX_ . "orders 
        WHERE id_order = " . (int)$idOrder
    );
    // Preparar lista de productos
    $productListHtml = '';
    foreach ($orderDetails as $product) {
        $productListHtml .= '<li>' . $product['product_name'] . ' (x' . $product['product_quantity'] . ') - ' . Tools::displayPrice($product['total_price_tax_incl']) . '</li>';
    }

                    // Enviar correo
                    if (Mail::Send(
                        (int)$this->context->language->id, // ID de idioma
                        'pickup_reminder', // Nombre del archivo de plantilla (sin extensión)
                        $this->l('Recordatorio de Recogida'), // Asunto del correo
                        [
                            '{customer_name}' => htmlspecialchars($customerName),
                            '{pickup_day}' => htmlspecialchars($formattedDate),
                            '{pickup_time}' => htmlspecialchars($pickupHour),
                            '{product_list}' => $productListHtml,
                            '{total}' => Tools::displayPrice($orderTotal),
                        ],
                        $email, // Dirección de correo del destinatario
                        null, // Nombre del destinatario (opcional)
                        null, // Dirección de correo del remitente (opcional)
                        null, // Nombre del remitente (opcional)
                        null, // Archivos adjuntos (opcional)
                        null, // Modo de envío (opcional)
                        _PS_MODULE_DIR_ . 'horariosBackOffice/mails/es' // Ruta personalizada a las plantillas
                    )) {
                        $output .= $this->displayConfirmation($this->l('Correo enviado correctamente.'));
                        Db::getInstance()->update(
                            'pickup_reminders',
                            ['status' => 'sent'],
                            'id_order = ' . $idOrder
                        );
                    } else {
                        $output .= $this->displayError($this->l('Error al enviar el correo.'));
                    }
                }
            }
        }
        
        // Eliminar opción
        if (Tools::isSubmit('deleteOption')) {
            $idOption = (int) Tools::getValue('id');
            if ($idOption) {
                Db::getInstance()->delete('pickup_options', 'id_pickup_option = ' . $idOption);
                $output .= $this->displayConfirmation($this->l('Opción de recogida eliminada con éxito.'));
            }
        }

        // Editar opción
        if (Tools::isSubmit('editOption')) {
            $idOption = (int) Tools::getValue('id');
            if ($idOption) {
                $option = Db::getInstance()->getRow("SELECT * FROM `" . _DB_PREFIX_ . "pickup_options` WHERE id_pickup_option = " . $idOption);
                if ($option) {
                    $_POST['carrier_name'] = $option['carrier_name'];
                    $_POST['schedule'] = json_decode($option['schedule'], true);
                }
            }
        }

        // Guardar cambios
        if (Tools::isSubmit('savePickupOptions')) {
            $carrierName = Tools::getValue('carrier_name');
            $scheduleData = Tools::getValue('schedule');
            $schedule = [];
        
            if ($scheduleData && is_array($scheduleData)) {
                foreach ($scheduleData as $day => $timeSlots) {
                    if (isset($timeSlots['start_time']) && is_array($timeSlots['start_time'])) {
                        foreach ($timeSlots['start_time'] as $index => $startTime) {
                            // Validar si los índices existen y no están vacíos
                            $startAmpm = isset($timeSlots['start_ampm'][$index]) ? $timeSlots['start_ampm'][$index] : '';
                            $endTime = isset($timeSlots['end_time'][$index]) ? $timeSlots['end_time'][$index] : '';
                            $endAmpm = isset($timeSlots['end_ampm'][$index]) ? $timeSlots['end_ampm'][$index] : '';
            
                            if (!empty($startTime) && !empty($startAmpm) && !empty($endTime) && !empty($endAmpm)) {
                                $schedule[$day][] = [
                                    'start_time' => pSQL($startTime), // Guarda solo la hora
                                    'end_time' => pSQL($endTime),     // Guarda solo la hora
                                    'start_ampm' => pSQL($startAmpm),
                                    'end_ampm' => pSQL($endAmpm)
                                ];
                                
                            }
                        }
                    }
                }
            }
            
        
// Guardar en la base de datos
$idOption = Tools::getValue('id');
if ($idOption) {
    // Actualización de una opción existente por ID
    Db::getInstance()->update('pickup_options', [
        'carrier_name' => pSQL($carrierName),
        'schedule' => json_encode($schedule, JSON_UNESCAPED_UNICODE),
    ], 'id_pickup_option = ' . (int)$idOption);
} else {
    // Verificar si ya existe una opción con el mismo carrier_name
    $existingOption = Db::getInstance()->getValue("SELECT id_pickup_option FROM `" . _DB_PREFIX_ . "pickup_options` WHERE carrier_name = '" . pSQL($carrierName) . "'");
    if ($existingOption) {
        // Actualiza si existe
        Db::getInstance()->update('pickup_options', [
            'schedule' => json_encode($schedule, JSON_UNESCAPED_UNICODE),
        ], 'id_pickup_option = ' . (int)$existingOption);
    } else {
        // Inserta si no existe
        Db::getInstance()->insert('pickup_options', [
            'carrier_name' => pSQL($carrierName),
            'schedule' => json_encode($schedule, JSON_UNESCAPED_UNICODE),
        ]);
    }
}


            $output .= $this->displayConfirmation($this->l('Opciones de recogida guardadas con éxito.'));
            $_POST['carrier_name'] = '';
            $_POST['schedule'] = [];
        }
        

      // Guardar o actualizar recordatorio
        // Guardar o actualizar recordatorio
    if (Tools::isSubmit('saveReminder')) {
        $idOrder = (int) Tools::getValue('id_order');
        $notificationHoursBefore = (int) Tools::getValue('notification_hours_before');

        if ($idOrder && $notificationHoursBefore >= 0) {
            $pickupTimeRange = Db::getInstance()->getValue("SELECT pickup_time FROM " . _DB_PREFIX_ . "cart_pickup_selection cps
                                                            INNER JOIN " . _DB_PREFIX_ . "orders o ON cps.id_cart = o.id_cart
                                                            WHERE o.id_order = $idOrder");

            if ($pickupTimeRange) {
                $pickupStartTime = explode(' - ', $pickupTimeRange)[0];
                $pickupStartTime24 = date('Y-m-d H:i:s', strtotime($pickupStartTime));
                $reminderTime = date('Y-m-d H:i:s', strtotime("$pickupStartTime24 - $notificationHoursBefore hours"));

                $existingReminder = Db::getInstance()->getValue("SELECT id_reminder FROM " . _DB_PREFIX_ . "pickup_reminders WHERE id_order = " . $idOrder);

                if ($existingReminder) {
                    Db::getInstance()->update('pickup_reminders', [
                        'reminder_time' => pSQL($reminderTime),
                        'notification_hours_before' => $notificationHoursBefore,
                        'status' => 'pending',
                    ], 'id_reminder = ' . (int)$existingReminder);
                } else {
                    Db::getInstance()->insert('pickup_reminders', [
                        'id_order' => $idOrder,
                        'id_customer' => (int) Db::getInstance()->getValue("SELECT id_customer FROM " . _DB_PREFIX_ . "orders WHERE id_order = " . $idOrder),
                        'reminder_time' => pSQL($reminderTime),
                        'notification_hours_before' => $notificationHoursBefore,
                        'status' => 'pending',
                    ]);
                }
            }
        }
    }
    

    // Renderizar vista de recordatorios o formulario principal
    if (Tools::isSubmit('viewReminders')) {
        $output .= $this->renderReminderList();
    } else {
        $output .= $this->renderForm();
        $output .= $this->renderSchedulePreview();
    }

    return $output;
    }
    
    


    private function renderForm()
    {
        $existingOptions = Db::getInstance()->getRow("SELECT * FROM `" . _DB_PREFIX_ . "pickup_options`");
        $carrierName = isset($existingOptions['carrier_name']) ? $existingOptions['carrier_name'] : '';
        $schedule = isset($existingOptions['schedule']) ? json_decode($existingOptions['schedule'], true) : [];
        $daysOfWeek = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    
        ob_start();
        ?>
        <div id="schedule-config" style="margin-top: 20px; border: 1px solid #d3d3d3; border-radius: 8px; padding: 15px;">
            <p style="font-size: 14px; color: #666;">Agrega horarios para cada día, especificando las franjas horarias y si son AM o PM. Puedes eliminar o añadir horarios según sea necesario.</p>
            <?php foreach ($daysOfWeek as $day): ?>
    <div class="day-config" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ccc; border-radius: 5px; background: #f9f9f9;">
        <h4 style="margin-bottom: 10px; color: #333; font-size: 16px;"><?php echo $day; ?></h4>
        <button type="button" class="btn btn-sm btn-primary add-time-slot" data-day="<?php echo $day; ?>" style="margin-bottom: 10px;">+ Agregar Horario</button>
        <div class="time-slots" data-day="<?php echo $day; ?>">
            <?php if (isset($schedule[$day])): ?>
                <?php foreach ($schedule[$day] as $timeSlot): ?>
                    <div class="time-slot" style="margin-bottom: 8px; display: flex; align-items: center;">
                        <!-- Hora inicial -->
                        <input type="text" name="schedule[<?php echo $day; ?>][start_time][]" value="<?php echo $timeSlot['start_time']; ?>" placeholder="Ej: 8:30" style="margin-right: 10px; width: 80px;" class="form-control form-control-sm">
                        <select name="schedule[<?php echo $day; ?>][start_ampm][]" class="form-control form-control-sm" style="margin-right: 10px; width: 70px;">
                            <option value="AM" <?php echo (isset($timeSlot['start_ampm']) && $timeSlot['start_ampm'] === 'AM') ? 'selected' : ''; ?>>AM</option>
                            <option value="PM" <?php echo (isset($timeSlot['start_ampm']) && $timeSlot['start_ampm'] === 'PM') ? 'selected' : ''; ?>>PM</option>
                        </select>
                        
                        <!-- Hora final -->
                        <input type="text" name="schedule[<?php echo $day; ?>][end_time][]" value="<?php echo $timeSlot['end_time']; ?>" placeholder="Ej: 9:30" style="margin-right: 10px; width: 80px;" class="form-control form-control-sm">
                        <select name="schedule[<?php echo $day; ?>][end_ampm][]" class="form-control form-control-sm" style="margin-right: 10px; width: 70px;">
                            <option value="AM" <?php echo (isset($timeSlot['end_ampm']) && $timeSlot['end_ampm'] === 'AM') ? 'selected' : ''; ?>>AM</option>
                            <option value="PM" <?php echo (isset($timeSlot['end_ampm']) && $timeSlot['end_ampm'] === 'PM') ? 'selected' : ''; ?>>PM</option>
                        </select>

                        <button type="button" class="btn btn-sm btn-danger remove-time-slot">Eliminar</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                document.querySelectorAll(".add-time-slot").forEach(button => {
    button.addEventListener("click", function () {
        const day = this.dataset.day;
        const container = document.querySelector(`.time-slots[data-day="${day}"]`);
        const slot = document.createElement("div");
        slot.classList.add("time-slot");
        slot.style.display = "flex";
        slot.style.alignItems = "center";
        slot.style.marginBottom = "8px";
        slot.innerHTML = `
            <!-- Hora inicial -->
            <input type="text" name="schedule[${day}][start_time][]" placeholder="Ej: 8:30" style="margin-right: 10px; width: 80px;" class="form-control form-control-sm">
            <select name="schedule[${day}][start_ampm][]" class="form-control form-control-sm" style="margin-right: 10px; width: 70px;">
                <option value="AM">AM</option>
                <option value="PM">PM</option>
            </select>
            
            <!-- Hora final -->
            <input type="text" name="schedule[${day}][end_time][]" placeholder="Ej: 9:30" style="margin-right: 10px; width: 80px;" class="form-control form-control-sm">
            <select name="schedule[${day}][end_ampm][]" class="form-control form-control-sm" style="margin-right: 10px; width: 70px;">
                <option value="AM">AM</option>
                <option value="PM">PM</option>
            </select>

            <button type="button" class="btn btn-sm btn-danger remove-time-slot">Eliminar</button>
        `;
        container.appendChild(slot);
    });
});

    
                document.addEventListener("click", function (e) {
                    if (e.target.classList.contains("remove-time-slot")) {
                        e.target.closest(".time-slot").remove();
                    }
                });
            });
        </script>
        <?php
        $scheduleFormHtml = ob_get_clean();
    
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuración de Recogida'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Nombre del Transportista'),
                        'name' => 'carrier_name',
                        'required' => true,
                        'value' => $carrierName,
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->l('Horario por Día'),
                        'name' => 'schedule',
                        'html_content' => $scheduleFormHtml,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Guardar'),
                ],
            ],
        ];
    
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'savePickupOptions';
    
        $helper->fields_value = [
            'carrier_name' => $carrierName,
        ];
    $html = '<a href="' . AdminController::$currentIndex . '&configure=' . $this->name . '&viewReminders&token=' . Tools::getAdminTokenLite('AdminModules') . '" class="btn btn-primary" style="margin-bottom: 15px;">' . $this->l('Ver Recordatorios') . '</a>';

    return $html . $helper->generateForm([$fieldsForm]);
    }
    
    
    private function renderSchedulePreview()
    {
        $pickupOptions = Db::getInstance()->executeS("SELECT * FROM `" . _DB_PREFIX_ . "pickup_options`");
    
        if (!$pickupOptions) {
            return '<p style="font-size: 14px; color: #666;">' . $this->l('No se han configurado opciones de recogida.') . '</p>';
        }
    
        $html = '<h3 style="margin-top: 20px; color: #333;">' . $this->l('Horarios Configurados') . '</h3>';
        $html .= '<table class="table" style="width: 100%; border-collapse: collapse;">';
        $html .= '<thead style="background-color: #f1f1f1; border-bottom: 2px solid #ccc;">
                    <tr>
                        <th style="padding: 10px; text-align: left;">' . $this->l('Nombre del Transportista') . '</th>
                        <th style="padding: 10px; text-align: left;">' . $this->l('Horarios') . '</th>
                        <th style="padding: 10px; text-align: left;">' . $this->l('Acciones') . '</th>
                    </tr>
                  </thead>';
        $html .= '<tbody>';
    
        foreach ($pickupOptions as $option) {
            $schedule = json_decode($option['schedule'], true);
            $html .= '<tr>';
            $html .= '<td style="padding: 10px; border-bottom: 1px solid #ccc;">' . htmlspecialchars($option['carrier_name']) . '</td>';
            $html .= '<td style="padding: 10px; border-bottom: 1px solid #ccc;">';
    
            if (!empty($schedule)) {
                foreach ($schedule as $day => $timeSlots) {
                    $html .= '<strong>' . $day . ':</strong> ';
                    foreach ($timeSlots as $slot) {
                        // Usar start_time y end_time correctamente
                        $html .= htmlspecialchars($slot['start_time'] . ' - ' . $slot['end_time']) . ', ';
                    }
                    $html = rtrim($html, ', ') . '<br>';
                }
            }
    
            $html .= '</td>';
            $html .= '<td style="padding: 10px; border-bottom: 1px solid #ccc;">
                        <a href="' . AdminController::$currentIndex . '&configure=' . $this->name . '&editOption&id=' . $option['id_pickup_option'] . '&token=' . Tools::getAdminTokenLite('AdminModules') . '" class="btn btn-sm btn-warning">Editar</a>
                        <a href="' . AdminController::$currentIndex . '&configure=' . $this->name . '&deleteOption&id=' . $option['id_pickup_option'] . '&token=' . Tools::getAdminTokenLite('AdminModules') . '" class="btn btn-sm btn-danger" onclick="return confirm(\'¿Estás seguro de eliminar esta opción?\')">Eliminar</a>
                      </td>';
            $html .= '</tr>';
        }
    
        $html .= '</tbody></table>';
    
        return $html;
    }
    
    
    public function hookDisplayBeforeCarrier($params)
    {
        $cartId = $this->context->cart->id;
    
        // Obtener la selección guardada (si existe)
        $pickupSelection = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'cart_pickup_selection WHERE id_cart = ' . (int)$cartId);
    
        // Obtener las opciones de recogida desde la base de datos
        $pickupOptions = Db::getInstance()->executeS("SELECT * FROM `" . _DB_PREFIX_ . "pickup_options`");
    
        // Días de la semana en español
        $diasSemana = [
            'Monday' => 'Lunes',
            'Tuesday' => 'Martes',
            'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves',
            'Friday' => 'Viernes',
            'Saturday' => 'Sábado',
            'Sunday' => 'Domingo',
        ];
    
        // Calcular "Hoy" y "Mañana"
        $hoyNombre = $diasSemana[date('l')];
        $mananaNombre = $diasSemana[date('l', strtotime('+1 day'))];
    
        $hoyFecha = date('d') . ' ' . $this->mesEnEspanol(date('F'));
        $mananaFecha = date('d', strtotime('+1 day')) . ' ' . $this->mesEnEspanol(date('F', strtotime('+1 day')));
    
        $hoy = "{$hoyNombre} {$hoyFecha}";
        $manana = "{$mananaNombre} {$mananaFecha}";
    
        $horaActual = date('h:i A'); // Hora actual en formato de 12 horas (ej. 01:30 PM)
        $pickupData = [];
    
        foreach ($pickupOptions as $option) {
            $schedule = json_decode($option['schedule'], true);
    
            if (is_array($schedule)) {
                // Verificar y filtrar horarios para hoy
                if (isset($schedule[$hoyNombre]) && !empty($schedule[$hoyNombre])) {
                    $pickupData[$option['carrier_name']][$hoy] = array_filter($schedule[$hoyNombre], function ($slot) use ($horaActual) {
                        // Convertir tiempos al formato de 24 horas para comparación
                        $slotHoraInicio = date('H:i', strtotime($slot['start_time'] . ' ' . $slot['start_ampm']));
                        $horaActual24 = date('H:i', strtotime($horaActual));
    
                        // Filtrar horarios que empiezan después de la hora actual
                        return $slotHoraInicio >= $horaActual24;
                    });
                }
    
                // Verificar horarios para mañana (no requiere filtrado)
                if (isset($schedule[$mananaNombre]) && !empty($schedule[$mananaNombre])) {
                    $pickupData[$option['carrier_name']][$manana] = $schedule[$mananaNombre];
                }
            }
        }
    
        // Asignar datos a la plantilla
        $this->context->smarty->assign([
            'selected_pickup_day' => $pickupSelection['pickup_day'] ?? $hoy,
            'selected_pickup_start_time' => $pickupSelection['pickup_time'] ?? '',
            'pickup_data' => $pickupData,
        ]);
    }
    
    
    
    
    // Función auxiliar para traducir el mes
    private function mesEnEspanol($mesIngles)
    {
        $meses = [
            'January' => 'enero',
            'February' => 'febrero',
            'March' => 'marzo',
            'April' => 'abril',
            'May' => 'mayo',
            'June' => 'junio',
            'July' => 'julio',
            'August' => 'agosto',
            'September' => 'septiembre',
            'October' => 'octubre',
            'November' => 'noviembre',
            'December' => 'diciembre',
        ];
        return $meses[$mesIngles] ?? $mesIngles;
    }
    
    
    
    
    
    
    
    

    public function hookActionFrontControllerSetMedia($params)
{
    if (Tools::getValue('module') === 'horariosBackOffice' && Tools::getValue('controller') === 'saveSelection') {
        $this->saveSelection();
    }
}

public function saveSelection()
{
    $day = Tools::getValue('day');
    $time = Tools::getValue('time');
    $cartId = $this->context->cart->id;
    $customerId = $this->context->customer->id;

    // Eliminar cualquier selección previa
    Db::getInstance()->delete('cart_pickup_selection', 'id_cart = ' . (int)$cartId);

    // Guardar nueva selección solo si existen valores
    if (!empty($day) && !empty($time)) {
        Db::getInstance()->insert('cart_pickup_selection', [
            'id_cart' => (int)$cartId,
            'pickup_day' => pSQL($day),
            'pickup_time' => pSQL($time),
        ]);

        // También insertar un recordatorio en `pickup_reminders`
        $orderId = Db::getInstance()->getValue('SELECT id_order FROM ' . _DB_PREFIX_ . 'orders WHERE id_cart = ' . (int)$cartId);
        if ($orderId) {
            Db::getInstance()->insert('pickup_reminders', [
                'id_order' => (int)$orderId,
                'id_customer' => (int)$customerId,
                'reminder_time' => date('Y-m-d H:i:s'), // Fecha y hora actuales
                'status' => 'pending',
            ]);
        }

        die(json_encode(['success' => true, 'message' => 'Selección guardada correctamente.']));
    }

    die(json_encode(['success' => false, 'message' => 'Selección no guardada.']));
}



private function renderReminderList()
{
    $sql = "SELECT 
                o.id_order, 
                c.firstname, 
                c.lastname, 
                c.email, 
                a.phone, 
                cps.pickup_day, 
                cps.pickup_time, 
                r.reminder_time, 
                r.notification_hours_before, 
                r.status 
            FROM " . _DB_PREFIX_ . "orders o
            INNER JOIN " . _DB_PREFIX_ . "cart_pickup_selection cps ON o.id_cart = cps.id_cart
            LEFT JOIN " . _DB_PREFIX_ . "customer c ON o.id_customer = c.id_customer
            LEFT JOIN " . _DB_PREFIX_ . "address a ON o.id_address_delivery = a.id_address
            LEFT JOIN " . _DB_PREFIX_ . "pickup_reminders r ON o.id_order = r.id_order";

    $orders = Db::getInstance()->executeS($sql);

    if (!$orders) {
        return '<p>' . $this->l('No hay pedidos con selección de horarios.') . '</p>';
    }

    $html = '<h3>' . $this->l('Pedidos con Horarios Seleccionados') . '</h3>';
    $html .= '<table class="table">';
    $html .= '<thead>
                <tr>
                    <th>' . $this->l('ID Pedido') . '</th>
                    <th>' . $this->l('Nombre Cliente') . '</th>
                    <th>' . $this->l('Correo Electrónico') . '</th>
                    <th>' . $this->l('Teléfono') . '</th>
                    <th>' . $this->l('Día de Recogida') . '</th>
                    <th>' . $this->l('Hora de Recogida') . '</th>
                    <th>' . $this->l('Horas Antes') . '</th>
                    <th>' . $this->l('Acciones') . '</th>
                </tr>
              </thead>';
    $html .= '<tbody>';

    foreach ($orders as $order) {
        $notificationHours = $order['notification_hours_before'] ?: $this->l('No configurado');
        $html .= '<tr>';
        $html .= '<td>' . $order['id_order'] . '</td>';
        $html .= '<td>' . htmlspecialchars($order['firstname'] . ' ' . $order['lastname']) . '</td>';
        $html .= '<td>' . htmlspecialchars($order['email']) . '</td>';
        $html .= '<td>' . htmlspecialchars($order['phone']) . '</td>';
        $html .= '<td>' . htmlspecialchars($order['pickup_day']) . '</td>';
        $html .= '<td>' . htmlspecialchars($order['pickup_time']) . '</td>';
        $html .= '<td>' . $notificationHours . '</td>';
        $html .= '<td>
                    <form method="post" action="' . AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '">
                        <input type="hidden" name="id_order" value="' . $order['id_order'] . '">
                        <label for="notification_hours_before">' . $this->l('Horas Antes:') . '</label>
                        <input type="number" name="notification_hours_before" value="' . $order['notification_hours_before'] . '" required>
                        <button type="submit" class="btn btn-primary btn-sm" name="saveReminder">' . $this->l('Guardar') . '</button>
                        <button type="submit" class="btn btn-success btn-sm" name="sendPickupReminder">' . $this->l('Enviar Correo') . '</button>
                    </form>
                  </td>';
        $html .= '</tr>';
    }

    $html .= '</tbody>';
    $html .= '</table>';

    return $html;
}

public function processReminders()
{
    // Obtener recordatorios pendientes cuya hora ya ha pasado
    $reminders = Db::getInstance()->executeS("
        SELECT 
            r.id_reminder,
            r.reminder_time,
            r.id_customer,
            r.id_order,
            c.email,
            CONCAT(c.firstname, ' ', c.lastname) AS customer_name,
            cps.pickup_time
        FROM " . _DB_PREFIX_ . "pickup_reminders r
        INNER JOIN " . _DB_PREFIX_ . "customer c ON r.id_customer = c.id_customer
        INNER JOIN " . _DB_PREFIX_ . "orders o ON r.id_order = o.id_order
        INNER JOIN " . _DB_PREFIX_ . "cart_pickup_selection cps ON o.id_cart = cps.id_cart
        WHERE r.status = 'pending' AND r.reminder_time <= NOW()
    ");

    foreach ($reminders as $reminder) {
        $idOrder = $reminder['id_order'];
        $customerName = $reminder['customer_name'];
        $email = $reminder['email'];
        $pickupTime = $reminder['pickup_time'];

        // Formatear fecha y hora
        $pickupDay = date('l j F Y', strtotime($reminder['reminder_time']));
        $diasSemana = [
            'Monday' => 'Lunes',
            'Tuesday' => 'Martes',
            'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves',
            'Friday' => 'Viernes',
            'Saturday' => 'Sábado',
            'Sunday' => 'Domingo'
        ];
        $meses = [
            'January' => 'enero',
            'February' => 'febrero',
            'March' => 'marzo',
            'April' => 'abril',
            'May' => 'mayo',
            'June' => 'junio',
            'July' => 'julio',
            'August' => 'agosto',
            'September' => 'septiembre',
            'October' => 'octubre',
            'November' => 'noviembre',
            'December' => 'diciembre'
        ];
        $pickupDay = strtr($pickupDay, $diasSemana);
        $pickupDay = strtr($pickupDay, $meses);

        $pickupHour = explode(' - ', $pickupTime)[0];

        // Obtener detalles del pedido
        $orderDetails = Db::getInstance()->executeS("
            SELECT 
                pl.name AS product_name, 
                od.product_quantity, 
                od.total_price_tax_incl 
            FROM " . _DB_PREFIX_ . "order_detail od
            INNER JOIN " . _DB_PREFIX_ . "product_lang pl ON od.product_id = pl.id_product AND pl.id_lang = " . (int)$this->context->language->id . "
            WHERE od.id_order = " . (int)$idOrder
        );

        $orderTotal = Db::getInstance()->getValue("
            SELECT total_paid_tax_incl 
            FROM " . _DB_PREFIX_ . "orders 
            WHERE id_order = " . (int)$idOrder
        );

        $productListHtml = '';
        foreach ($orderDetails as $product) {
            $productListHtml .= '<li>' . htmlspecialchars($product['product_name']) . ' (x' . (int)$product['product_quantity'] . ') - ' . Tools::displayPrice($product['total_price_tax_incl']) . '</li>';
        }

        // Enviar correo
        if (Mail::Send(
            (int)$this->context->language->id,
            'pickup_reminder',
            $this->l('Recordatorio de Recogida'),
            [
                '{customer_name}' => htmlspecialchars($customerName),
                '{pickup_day}' => htmlspecialchars($pickupDay),
                '{pickup_time}' => htmlspecialchars($pickupHour),
                '{product_list}' => $productListHtml,
                '{total}' => Tools::displayPrice($orderTotal),
            ],
            $email,
            null,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . 'horariosBackOffice/mails/es'
        )) {
            // Marcar el recordatorio como enviado
            Db::getInstance()->update(
                'pickup_reminders',
                ['status' => 'sent'],
                'id_reminder = ' . (int)$reminder['id_reminder']
            );
        }
    }
}


}
