<?php
/**
 * Modelo de permisos: catálogo de módulos de la tabla `permisos`.
 *
 * @package Prestamos\Modelos
 */
require_once __DIR__ . "/../config/Database.php";

/**
 * Consulta del catálogo de permisos (RF-07).
 */
class Permisos
{
    /**
     * Constructor vacío; se mantiene por compatibilidad con ajax/.
     */
    public function __construct()
    {
    }

    /**
     * Lista todos los permisos disponibles.
     *
     * @return Resultado
     */
    public function listar()
    {
        return Consulta::listar("SELECT * FROM permisos");
    }
}
