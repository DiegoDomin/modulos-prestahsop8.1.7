<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class Campos extends Module
{
    public function __construct()
    {
        $this->name = 'campos'; // Mantén el nombre del módulo.
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Diego';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Campos Extra y Opciones de Recogida en Tienda');
        $this->description = $this->l('Agrega campos adicionales al formulario de direcciones y añade días y horarios dinámicos para la recogida en tienda en el paso de envío.');
        $this->ps_versions_compliancy = ['min' => '1.7.7', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionCustomerAddressFormBuilderModifier')
            && $this->registerHook('actionCustomerAddressFormDataProvider')
            && $this->registerHook('displayBeforeCarrier')
            && $this->addCustomFieldsToDatabase();

    }


    private function addCustomFieldsToDatabase()
    {
        $fields = [
            'need_tax_invoice' => "TINYINT(1) NOT NULL DEFAULT 0",
            'razon_social' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'nit_number' => "VARCHAR(50) NOT NULL DEFAULT ''",
            'latitude' => "VARCHAR(50) NOT NULL DEFAULT ''",
            'longitude' => "VARCHAR(50) NOT NULL DEFAULT ''",
        ];
    
        $columns = Db::getInstance()->executeS('SHOW COLUMNS FROM `'._DB_PREFIX_.'address`');
    
        $existingFields = [];
        foreach ($columns as $column) {
            $existingFields[] = $column['Field'];
        }
    
        $sql = [];
        foreach ($fields as $field => $definition) {
            if (!in_array($field, $existingFields)) {
                $sql[] = "ADD `$field` $definition";
            }
        }
    
        if (!empty($sql)) {
            $query = 'ALTER TABLE `'._DB_PREFIX_.'address` ' . implode(', ', $sql);
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }
    
        return true;
    }
    
    

    public function uninstall()
    {
        return parent::uninstall()
        && $this->unregisterHook('actionCustomerAddressFormBuilderModifier')
        && $this->unregisterHook('actionCustomerAddressFormDataProvider')
        && $this->unregisterHook('displayBeforeCarrier')
        && $this->removeCustomFieldsFromDatabase();
     
    }
    private function removeCustomFieldsFromDatabase()
    {
        $sql = [];
        $sql[] = "ALTER TABLE `"._DB_PREFIX_."address` 
                  DROP COLUMN `need_tax_invoice`,
                  DROP COLUMN `razon_social`,
                  DROP COLUMN `nit_number`,
                  DROP COLUMN `latitude`,
                  DROP COLUMN `longitude`;";
    
        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Añade campos al form builder del formulario de dirección del front-office.
     */
    public function hookActionCustomerAddressFormBuilderModifier($params)
    {
        $formBuilder = $params['form_builder'];

        
        
        $formBuilder
            ->add('need_tax_invoice', CheckboxType::class, [
                'label' => $this->l('¿Necesitas Comprobante de Crédito Fiscal?'),
                'required' => false,
            ])
            ->add('razon_social', TextType::class, [
                'label' => $this->l('Razón Social'),
                'required' => false,
            ])
            ->add('nit_number', TextType::class, [
                'label' => $this->l('Número de NIT'),
                'required' => false,
            ])
            ->add('latitude', TextType::class, [
                'label' => $this->l('Latitud'),
                'required' => false,
            ])
            ->add('longitude', TextType::class, [
                'label' => $this->l('Longitud'),
                'required' => false,
            ]);

    }

    /**
     * Maneja los datos del formulario antes de guardarlos.
     */
    public function hookActionCustomerAddressFormDataProvider($params)
    {
        if (isset($params['data'])) {
            $deleted = isset($params['data']['deleted']) ? $params['data']['deleted'] : 0;

            $params['data'] = array_merge($params['data'], [
                'alias' => Tools::getValue('alias', $params['data']['alias']),
                'firstname' => Tools::getValue('firstname', $params['data']['firstname']),
                'lastname' => Tools::getValue('lastname', $params['data']['lastname']),
                'company' => Tools::getValue('company', $params['data']['company']),
                'vat_number' => Tools::getValue('vat_number', $params['data']['vat_number']),
                'address1' => Tools::getValue('address1', $params['data']['address1']),
                'address2' => Tools::getValue('address2', $params['data']['address2']),
                'postcode' => Tools::getValue('postcode', $params['data']['postcode']),
                'city' => Tools::getValue('city', $params['data']['city']),
                'id_country' => Tools::getValue('id_country', $params['data']['id_country']),
                'phone_mobile' => Tools::getValue('phone_mobile', $params['data']['phone_mobile'] ?: ''), 
                'dni' => Tools::getValue('dni', $params['data']['dni'] ?: ''), 
                'need_tax_invoice' => (bool)Tools::getValue('need_tax_invoice', 0),
                'razon_social' => Tools::getValue('razon_social', $params['data']['razon_social']),
                'nit_number' => Tools::getValue('nit_number', $params['data']['nit_number']),
                'latitude' => Tools::getValue('latitude', isset($params['data']['latitude']) ? $params['data']['latitude'] : null),
                'longitude' => Tools::getValue('longitude', $params['data']['longitude']),

            ]);
        }
    }

 
    
   
    /**
     * Hook para asignar los días y horarios dinámicos antes de cargar la vista del paso de envío.
     */
    public function hookDisplayBeforeCarrier($params)
    {
        // Datos dinámicos (puedes modificarlos según tus necesidades)
        $pickupDays = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
        $pickupTimes = ['10:00 AM - 11:00 AM', '11:00 AM - 12:00 PM', '2:00 PM - 3:00 PM'];

        // Asignar datos a Smarty
        $this->context->smarty->assign([
            'pickup_days' => $pickupDays,
            'pickup_times' => $pickupTimes,
        ]);
    }


}
