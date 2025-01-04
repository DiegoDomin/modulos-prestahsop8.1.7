<?php

class Address extends AddressCore
{
    public static $definition = [
        'table' => 'address',
        'primary' => 'id_address',
        'fields' => [
            // Campos estándar
            'id_country' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_state' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'alias' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'firstname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 255],
            'lastname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 255],
            'address1' => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'required' => true, 'size' => 128],
            'address2' => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'size' => 128],
            'postcode' => ['type' => self::TYPE_STRING, 'validate' => 'isPostCode', 'size' => 12],
            'city' => ['type' => self::TYPE_STRING, 'validate' => 'isCityName', 'required' => true, 'size' => 64],
            'phone' => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 32],
            'vat_number' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32],
            'company' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'], // Campo estándar necesario
            'phone_mobile' => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 32, 'default' => ''],
            'dni' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 16, 'default' => ''],

            // Campos personalizados
'need_tax_invoice' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false, 'default' => 0],
'razon_social' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255, 'default' => ''],
'nit_number' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50, 'default' => ''],
'latitude' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50, 'default' => ''],
'longitude' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50, 'default' => ''],
'other' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255, 'default' => ''], // Evita null


        ],
    ];
}
