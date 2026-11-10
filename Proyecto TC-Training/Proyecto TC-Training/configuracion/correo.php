<?php
// Configuración SMTP para el envío de reportes.
// IMPORTANTE: no subas este archivo a GitHub (agrégalo al .gitignore).
//
// Con Gmail: activa la verificación en 2 pasos y crea una "Contraseña de aplicación"
// (Cuenta de Google > Seguridad > Contraseñas de aplicaciones). Pégala SIN espacios.
return [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'secure'     => 'tls',                 // 'tls' con puerto 587, 'ssl' con puerto 465
    'username'   => 'tu_correo@gmail.com',
    'password'   => 'tucontraseñadeaplicacion',
    'from_email' => 'tu_correo@gmail.com',
    'from_name'  => 'TC-Control',
];
