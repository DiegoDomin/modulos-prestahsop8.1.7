<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class PackagingOptions extends Module
{
    public function __construct()
    {
        $this->name = 'packagingoptions';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'YourName';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Packaging Options');
        $this->description = $this->l('Add packaging options to the checkout process.');

        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    private function registerCustomHook($hookName)
{
    $idHook = Hook::getIdByName($hookName);
    if (!$idHook) {
        $newHook = new Hook();
        $newHook->name = $hookName;
        $newHook->title = $hookName;
        $newHook->add();
    }
}

public function install()
{
    $this->registerCustomHook('displayReviewCheckout');
    
    return parent::install() &&
        $this->registerHook('displayReviewCheckout') &&
        $this->registerHook('displayBeforeCarrier') &&
        $this->registerHook('actionFrontControllerSetMedia') &&
        $this->registerHook('displayOrderDetail') &&
        $this->registerHook('actionValidateOrder') &&
        $this->installDb() &&
        Configuration::updateValue('PACKAGING_OPTION_DEFAULT', 'recycled_box');
}

    private function installDb()
    {
        $sql1 = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'packaging_options` (
            `id_cart` INT(10) UNSIGNED NOT NULL,
            `packaging_option` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id_cart`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        $sql2 = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'order_packaging` (
            `id_order` INT(10) UNSIGNED NOT NULL,
            `packaging_option` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id_order`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        $sql3 = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'packaging_options_data` (
            `id_option` INT(10) UNSIGNED AUTO_INCREMENT,
            `title` VARCHAR(100) NOT NULL,
            `description` VARCHAR(80) NOT NULL,
            `image` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id_option`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';
        return Db::getInstance()->execute($sql1) && Db::getInstance()->execute($sql2) &&   Db::getInstance()->execute($sql3);
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            $this->uninstallDb() &&
            Configuration::deleteByName('PACKAGING_OPTION_DEFAULT');
    }

    private function uninstallDb()
    {
        $sql1 = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packaging_options`;';
        $sql2 = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'order_packaging`;';
        $sql3 = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packaging_options_data`;';

        return Db::getInstance()->execute($sql1) && Db::getInstance()->execute($sql2) &&
        Db::getInstance()->execute($sql3);
    }

    public function hookDisplayBeforeCarrier($params)
    {
        $cartId = $this->context->cart->id;
    
        $selectedOption = Db::getInstance()->getValue('SELECT `packaging_option` FROM `' . _DB_PREFIX_ . 'packaging_options` WHERE `id_cart` = ' . (int)$cartId);
    
        $options = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'packaging_options_data`');
    
        $this->context->smarty->assign([
            'selected_option' => $selectedOption ?? '',
            'packaging_options' => $options,
        ]);
    
        return $this->fetch('module:packagingoptions/views/templates/hook/displayBeforeCarrier.tpl');
    }
    

    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->registerStylesheet(
            'packagingoptions-css',
            'modules/' . $this->name . '/views/css/packagingoptions.css'
        );
    }

    public function hookDisplayOrderDetail($params)
    {
        $order = $params['order'];

        $selectedOption = Db::getInstance()->getValue('SELECT `packaging_option` FROM `' . _DB_PREFIX_ . 'order_packaging` WHERE `id_order` = ' . (int)$order->id);

        if ($selectedOption) {
            return '<p>' . $this->l('Selected Packaging Option: ') . $selectedOption . '</p>';
        }
    }

    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $cartId = $order->id_cart;

        $selectedOption = Db::getInstance()->getValue('SELECT `packaging_option` FROM `' . _DB_PREFIX_ . 'packaging_options` WHERE `id_cart` = ' . (int)$cartId);

        if ($selectedOption) {
            Db::getInstance()->insert('order_packaging', [
                'id_order' => (int)$order->id,
                'packaging_option' => pSQL($selectedOption),
            ]);
        }
    }
    public function hookDisplayReviewCheckout($params)
    {
        $cartId = $this->context->cart->id;
    
        // Obtener la opción seleccionada desde la tabla packaging_options
        $selectedOption = Db::getInstance()->getValue('SELECT `packaging_option` FROM `' . _DB_PREFIX_ . 'packaging_options` WHERE `id_cart` = ' . (int)$cartId);
    
        if ($selectedOption) {
            // Mostrar el resumen de la opción seleccionada
            $optionText = $selectedOption == 'recycled_box' ? $this->l('Recycled Boxes') : $this->l('Plastic Bags');
            return '<p><strong>' . $this->l('Packaging Option:') . '</strong> ' . $optionText . '</p>';
        }
    }
    public function getContent()
    {
        $output = '';
    
        if (Tools::isSubmit('submitPackagingOption')) {
            $title = Tools::getValue('title');
            $description = Tools::getValue('description');
            $imageUrl = Tools::getValue('image');
            $uploadedImage = $_FILES['uploaded_image'];
        
            if (strlen($description) > 80) {
                $output .= $this->displayError($this->l('The description cannot exceed 80 characters.'));
            } else {
                // Validar y manejar la subida de imágenes
                $imagePath = '';
                if (!empty($uploadedImage['name'])) {
                    $uploadDir = _PS_IMG_DIR_ . 'packaging/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
        
                    $fileName = uniqid() . '_' . basename($uploadedImage['name']);
                    $targetFile = $uploadDir . $fileName;
        
                    // Validar el archivo
                    if (move_uploaded_file($uploadedImage['tmp_name'], $targetFile)) {
                        $imagePath = _PS_BASE_URL_ . __PS_BASE_URI__ . 'img/packaging/' . $fileName;
                    } else {
                        $output .= $this->displayError($this->l('Failed to upload the image.'));
                    }
                }
        
                // Usar la URL proporcionada si no se subió un archivo
                if (!$imagePath && !empty($imageUrl)) {
                    $imagePath = $imageUrl;
                }
        
                // Insertar en la base de datos
                if ($imagePath) {
                    Db::getInstance()->insert('packaging_options_data', [
                        'title' => pSQL($title),
                        'description' => pSQL($description),
                        'image' => pSQL($imagePath),
                    ]);
        
                    $output .= $this->displayConfirmation($this->l('Packaging option added successfully.'));
                } else {
                    $output .= $this->displayError($this->l('porfavor pon una imagen correcta.'));
                }
            }
        }
        
    
        // Eliminar una opción de empaque
        if (Tools::isSubmit('deletePackagingOption')) {
            $idOption = (int)Tools::getValue('id_option');
            if ($idOption) {
                Db::getInstance()->delete('packaging_options_data', 'id_option = ' . (int)$idOption);
                $output .= $this->displayConfirmation($this->l('Packaging option deleted successfully.'));
            }
        }
    
        // Obtener las opciones existentes
        $options = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'packaging_options_data`');
    
        // Crear formulario para agregar nuevas opciones
        $helper = new HelperForm();
        $helper->submit_action = 'submitPackagingOption';
        $helper->fields_value = [
            'title' => '',
            'description' => '',
            'image' => ''
        ];
    
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Agrega un nuevo opcion de empaque'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Tipo de empaque'),
                        'name' => 'title',
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Descripcion de empaque(maximo 100 caracteres)'),
                        'name' => 'description',
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('URL de la imagen (puedes tambien agregar una imagen en el siguiente campo,este campo es opcional)'),
                        'name' => 'image',
                    ],
                    [
                        'type' => 'file',
                        'label' => $this->l('Subir imagen'),
                        'name' => 'uploaded_image',
                        'desc' => $this->l('Sube una imagen desde tu computadora.'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-primary',
                ],
            ],
        ];
    
        $output .= $helper->generateForm([$fields_form]);
    
        // Mostrar la lista de opciones creadas
        if ($options) {
            $output .= '<h3>' . $this->l('Opciones de Empaques Creadas') . '</h3>';
            $output .= '<table class="table">
                <thead>
                    <tr>
                        <th>' . $this->l('ID') . '</th>
                        <th>' . $this->l('Tipo de Empaque') . '</th>
                        <th>' . $this->l('Descripción') . '</th>
                        <th>' . $this->l('Imagen') . '</th>
                        <th>' . $this->l('Acciones') . '</th>
                    </tr>
                </thead>
                <tbody>';
    
            foreach ($options as $option) {
                $output .= '<tr>
                    <td>' . $option['id_option'] . '</td>
                    <td>' . htmlspecialchars($option['title']) . '</td>
                    <td>' . htmlspecialchars($option['description']) . '</td>
                    <td><img src="' . htmlspecialchars($option['image']) . '" alt="' . htmlspecialchars($option['title']) . '" style="width: 60px; height: 60px;"></td>
                    <td>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="id_option" value="' . (int)$option['id_option'] . '">
                            <button type="submit" name="deletePackagingOption" class="btn btn-danger">' . $this->l('Eliminar') . '</button>
                        </form>
                    </td>
                </tr>';
            }
    
            $output .= '</tbody>
            </table>';
        } else {
            $output .= '<p>' . $this->l('No hay opciones de empaque creadas.') . '</p>';
        }
    
        return $output;
    }
    
}
