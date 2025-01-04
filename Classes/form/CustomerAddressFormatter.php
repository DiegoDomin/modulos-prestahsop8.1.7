<?php 

class CustomerAddressFormatter extends CustomerAddressFormatterCore
{
    public function getFormat()
    {
        // Llama al método original para obtener el formato existente
        $format = parent::getFormat();

        // Verifica si el campo ya existe antes de agregarlo
        if (!isset($format['need_tax_invoice'])) {
            $format['need_tax_invoice'] = (new FormField())
                ->setName('need_tax_invoice')
                ->setType('checkbox')
                ->setLabel('¿Necesitas Comprobante de Crédito Fiscal?');
        }

        if (!isset($format['nit_number'])) {
            $format['nit_number'] = (new FormField())
                ->setName('nit_number')
                ->setType('text')
                ->setLabel('Número de NIT')
                ->addAvailableValue(
                    'placeholder',
                    'Ingresa tu número de NIT'
                );
        }

        if (!isset($format['razon_social'])) {
            $format['razon_social'] = (new FormField())
                ->setName('razon_social')
                ->setType('text')
                ->setLabel('Razón Social')
                ->addAvailableValue(
                    'placeholder',
                    'Ingresa tu razón social'
                );
        }
        // Campo de latitud
        if (!isset($format['latitude'])) {
            $format['latitude'] = (new FormField())
                ->setName('latitude')
                ->setType('text') // Campo de texto
                ->setLabel('Latitude')
                ->addAvailableValue('placeholder', 'Ingrese latitud'); // Campo editable
        }
        
        // Campo de longitud
        if (!isset($format['longitude'])) {
            $format['longitude'] = (new FormField())
                ->setName('longitude')
                ->setType('text') // Campo de texto
                ->setLabel('Longitud')
                ->addAvailableValue('placeholder', 'Ingrese longitud'); // Campo editable
        }




            if (!isset($format['city'])) {
                $format['city'] = (new FormField())
                    ->setName('city')
                    ->setType('select') // Cambia el tipo a 'select'
                    ->setLabel('Municipio')
                    ->addAvailableValue('Antiguo Cuscatlan', 'Antiguo Cuscatlán')
                    ->addAvailableValue('Santa Tecla', 'Santa Tecla'); // Agrega más opciones según sea necesario
            }

        if (!isset($format['alias'])) {
            $format['alias'] = (new FormField())
                ->setName('alias')
                ->setType('text') // Sigue siendo un campo de texto
                ->setLabel('Punto de Referencia') // Cambia la etiqueta
                ->addAvailableValue('placeholder', 'Ingrese el punto de referencia'); // Placeholder opcional
        }


        return $format;
    }
}
