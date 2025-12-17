<?php

function db_config(): array {
    return [
        'DB_HOST' => 'localhost',
        'DB_PORT' => '3306',
        'DB_NAME' => 'informe2', // Según bd.sql, el nombre original era informe2
        'DB_USER' => 'root',
        'DB_PASS' => '',
        
        // Configuración de la URL base
        'APP_URL' => 'http://localhost/informe/',

        // Configuración de Correo (Mailtrap o similar por defecto para desarrollo)
        'MAIL_HOST' => 'sandbox.smtp.mailtrap.io',
        'MAIL_PORT' => '2525',
        'MAIL_USER' => '',
        'MAIL_PASS' => '',
        'MAIL_FROM' => 'admin@localhost',
        'MAIL_FROM_NAME' => 'Sistema Informes'
    ];
}
