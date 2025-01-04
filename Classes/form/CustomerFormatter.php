<?php

class CustomerFormatter extends CustomerFormatterCore
{
    public function getFormat()
    {
        $changingDate = parent::getFormat();

        // Verifica que el campo birthday exista
        if (isset($changingDate['birthday'])) {
            // Cambiar el tipo del campo "birthday" a "date"
            $changingDate['birthday']->setType('date');
            // Hacerlo obligatorio
            $changingDate['birthday']->setRequired(true);
        }

        return $changingDate;
    }
}
