<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class RestrictedZones extends Module
{
    public function __construct()
    {
        $this->name = 'restrictedzones';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Diego';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Zonas Restringidas');
        $this->description = $this->l('Permite configurar zonas restringidas para entregas.');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    private function createRestrictedZonesTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "restricted_zones` (
            `id_zone` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `polygon_coordinates` LONGTEXT NOT NULL,
            `polygon_color` VARCHAR(7) NOT NULL DEFAULT '#FF0000',
            `polygon_fill` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id_zone`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
        return Db::getInstance()->execute($sql);
    }

    private function deleteRestrictedZonesTable()
    {
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "restricted_zones`;";
        return Db::getInstance()->execute($sql);
    }

    public function install()
    {
        return parent::install()
            && $this->createRestrictedZonesTable()
            && $this->installTab(); // Registra la pestaña en el Back Office
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->deleteRestrictedZonesTable()
            && $this->uninstallTab(); // Elimina la pestaña del Back Office
    }

    public function getRestrictedZones()
    {
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "restricted_zones`";
        return Db::getInstance()->executeS($sql);
    }

    public function addRestrictedZone($name, $polygonCoordinates)
    {
        $sql = "INSERT INTO `" . _DB_PREFIX_ . "restricted_zones` (`name`, `polygon_coordinates`)
                VALUES ('" . pSQL($name) . "', '" . pSQL($polygonCoordinates) . "')";
        return Db::getInstance()->execute($sql);
    }

    private function installTab()
    {
        // Verifica si la pestaña ya existe
        $idTab = (int)Tab::getIdFromClassName('AdminRestrictedZones');
        if ($idTab) {
            return true; // Si ya existe, no la crea de nuevo
        }
    
        $tab = new Tab();
        $tab->class_name = 'AdminRestrictedZones';
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentModulesSf'); // Coloca la pestaña en la sección de Módulos
        $tab->module = $this->name;
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Zonas Restringidas';
        }
    
        return $tab->add();
    }
    

    private function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminRestrictedZones');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitRestrictedZonesConfig')) {
            $apiKey = Tools::getValue('GOOGLE_MAPS_API_KEY');
            Configuration::updateValue('GOOGLE_MAPS_API_KEY', $apiKey);
            $output .= $this->displayConfirmation($this->l('Configuración guardada.'));
        }
    
        $apiKey = Configuration::get('GOOGLE_MAPS_API_KEY');
    
        return $output . $this->renderForm($apiKey);
    }
    
    private function renderForm($apiKey)
    {
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Configuración de Google Maps'),
                'icon' => 'icon-cogs',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Clave de la API de Google Maps'),
                    'name' => 'GOOGLE_MAPS_API_KEY',
                    'required' => true,
                    'desc' => $this->l('Ingresa la clave de API de Google Maps para habilitar los mapas.'),
                    'value' => $apiKey,
                ],
            ],
            'submit' => [
                'title' => $this->l('Guardar'),
            ],
        ];
    
        $helper = new HelperForm();
        $helper->submit_action = 'submitRestrictedZonesConfig';
        $helper->fields_value['GOOGLE_MAPS_API_KEY'] = $apiKey;
    
        return $helper->generateForm($fieldsForm);
    }
    
}
