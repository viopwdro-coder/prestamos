<?php
/**
 * Modelo de clientes: acceso a la tabla `clientes`.
 *
 * @package Prestamos\Modelos
 */
require_once __DIR__ . "/../config/Database.php";

/**
 * Operaciones sobre los clientes del prestamista (RF-01).
 */
class Clientes
{
    /**
     * Constructor vacío; se mantiene por compatibilidad con ajax/.
     */
    public function __construct()
    {
    }

    /**
     * Registra un cliente nuevo y activo.
     *
     * @param string $cedula    Número de identificación.
     * @param string $nombre    Nombre completo.
     * @param string $direccion Dirección de residencia.
     * @param string $telefono  Teléfono de contacto.
     * @return bool true si se guardó.
     */
    public function insertar($cedula, $nombre, $direccion, $telefono)
    {
        $sql = "INSERT INTO clientes (cedula, nombre, direccion, telefono, estado)
                VALUES (?, ?, ?, ?, 1)";
        return Consulta::ejecutar($sql, [$cedula, $nombre, $direccion, $telefono]);
    }

    /**
     * Actualiza los datos de un cliente existente.
     *
     * @param int    $idcliente Identificador del cliente.
     * @param string $cedula    Número de identificación.
     * @param string $nombre    Nombre completo.
     * @param string $direccion Dirección.
     * @param string $telefono  Teléfono.
     * @return bool true si se actualizó.
     */
    public function editar($idcliente, $cedula, $nombre, $direccion, $telefono)
    {
        $sql = "UPDATE clientes
                SET cedula = ?, nombre = ?, direccion = ?, telefono = ?
                WHERE idcliente = ?";
        return Consulta::ejecutar($sql, [$cedula, $nombre, $direccion, $telefono, $idcliente]);
    }

    /**
     * Desactiva un cliente sin borrar su historial.
     *
     * @param int $idcliente Identificador del cliente.
     * @return bool
     */
    public function desactivar($idcliente)
    {
        return Consulta::ejecutar("UPDATE clientes SET estado = 0 WHERE idcliente = ?", [$idcliente]);
    }

    /**
     * Reactiva un cliente desactivado.
     *
     * @param int $idcliente Identificador del cliente.
     * @return bool
     */
    public function activar($idcliente)
    {
        return Consulta::ejecutar("UPDATE clientes SET estado = 1 WHERE idcliente = ?", [$idcliente]);
    }

    /**
     * Devuelve los datos de un cliente para editarlo.
     *
     * @param int $idcliente Identificador del cliente.
     * @return array|null Fila del cliente o null si no existe.
     */
    public function mostrar($idcliente)
    {
        return Consulta::fila("SELECT * FROM clientes WHERE idcliente = ?", [$idcliente]);
    }

    /**
     * Lista todos los clientes.
     *
     * @return Resultado
     */
    public function listar()
    {
        return Consulta::listar("SELECT * FROM clientes");
    }

    /**
     * Lista los clientes activos para una lista desplegable.
     *
     * @return Resultado
     */
    public function select()
    {
        return Consulta::listar(
            "SELECT idcliente, nombre FROM clientes WHERE estado = 1 ORDER BY nombre ASC"
        );
    }
}
