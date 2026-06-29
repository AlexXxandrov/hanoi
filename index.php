<?php
/**
 * Punto de entrada para despliegue web (Railway / Nixpacks).
 *
 * Nixpacks detecta una aplicación PHP cuando encuentra un "index.php"
 * o un "composer.json" en la raíz del proyecto y la sirve directamente.
 * Aquí simplemente cargamos el juego, que vive en un único archivo.
 */
require __DIR__ . '/hanoi.php';
