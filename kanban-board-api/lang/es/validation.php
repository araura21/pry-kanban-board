<?php

return [
    'required' => 'Completa el campo :attribute.',
    'string' => 'El campo :attribute debe ser texto.',
    'email' => 'Escribe un correo válido.',
    'unique' => 'Ese :attribute ya está registrado.',
    'confirmed' => 'Las contraseñas no coinciden.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'min' => ['string' => 'El campo :attribute debe tener al menos :min caracteres.', 'numeric' => 'El campo :attribute debe ser al menos :min.'],
    'max' => ['string' => 'El campo :attribute no puede superar :max caracteres.'],
    'attributes' => ['name' => 'nombre', 'email' => 'correo', 'password' => 'contraseña', 'titulo' => 'título', 'estado' => 'columna', 'version' => 'versión'],
];
