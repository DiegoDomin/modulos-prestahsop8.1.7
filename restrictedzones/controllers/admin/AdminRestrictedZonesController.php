<?php

class AdminRestrictedZonesController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        // Renderizar contenido principal
        $this->content = $this->renderForm();
        parent::initContent();
    }

    public function renderForm()
    {
        // Obtener las zonas restringidas desde la base de datos
        $zones = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'restricted_zones');

        // Pasar datos a Smarty
        $this->context->smarty->assign([
            'zones' => $zones,
            'module_dir' => _MODULE_DIR_ . $this->module->name . '/',
            'google_maps_api_key' => Configuration::get('GOOGLE_MAPS_API_KEY'),

        ]);

        // Cargar la plantilla del módulo
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/configure.tpl');
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitRestrictedZones')) {
            $idZone = Tools::getValue('id_zone'); // ID de la zona (si se está editando)
            $name = Tools::getValue('zone_name');
            $coordinates = Tools::getValue('zone_coordinates');
            $color = Tools::getValue('polygon_color', '#FF0000');
            $fill = Tools::getValue('polygon_fill') ? 1 : 0;
            if (empty($name) || empty($coordinates)) {
                $this->errors[] = $this->l('El nombre y las coordenadas son obligatorios.');
                return;
            }
    
            // Validar JSON
            if (json_decode($coordinates) === null) {
                $this->errors[] = $this->l('Las coordenadas del polígono no son válidas.');
                return;
            }
    
        // Insertar o actualizar
        if ($idZone) {
            $success = Db::getInstance()->update('restricted_zones', [
                'name' => pSQL($name),
                'polygon_coordinates' => pSQL($coordinates),
                'polygon_color' => pSQL($color),
                'polygon_fill' => (int)$fill,
            ], 'id_zone = ' . (int)$idZone);
        } else {
            $success = Db::getInstance()->insert('restricted_zones', [
                'name' => pSQL($name),
                'polygon_coordinates' => pSQL($coordinates),
                'polygon_color' => pSQL($color),
                'polygon_fill' => (int)$fill,
            ]);
        }
    
            if ($success) {
                $this->confirmations[] = $this->l('Zona restringida añadida correctamente.');
            } else {
                $this->errors[] = $this->l('Error al guardar la zona restringida.');
            }
    
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminRestrictedZones'));
        }
    }


    public function ajaxProcessDeleteZone()
    {
        $zoneId = Tools::getValue('id_zone');
        if (!$zoneId) {
            die(json_encode(['success' => false, 'message' => 'ID de zona no proporcionado.']));
        }
    
        $deleted = Db::getInstance()->delete('restricted_zones', 'id_zone = ' . (int)$zoneId);
    
        if ($deleted) {
            die(json_encode(['success' => true, 'message' => 'Zona eliminada correctamente.']));
        } else {
            die(json_encode(['success' => false, 'message' => 'Error al eliminar la zona.']));
        }
    }
    

}
