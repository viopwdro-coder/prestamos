<?php
/**
 * Modelo de gastos: acceso a la tabla `gastos`.
 *
 * @package Prestamos\Modelos
 */
require_once __DIR__ . "/../config/Database.php";

/**
 * Operaciones sobre los gastos operativos del negocio (RF-05).
 */
class Gastos
{
    /**
     * Constructor vacío; se mantiene por compatibilidad con ajax/.
     */
    public function __construct()
    {
    }

    /**
     * Registra un gasto.
     *
     * @param int    $idusuario Usuario responsable.
     * @param string $fecha     Fecha del gasto.
     * @param string $concepto  Descripción del gasto.
     * @param float  $gasto     Valor del gasto.
     * @return bool
     */
    public function insertar($idusuario, $fecha, $concepto, $gasto)
    {
        $sql = "INSERT INTO gastos (idusuario, fecha, concepto, gasto) VALUES (?, ?, ?, ?)";
        return Consulta::ejecutar($sql, [$idusuario, $fecha, $concepto, $gasto]);
    }

    /**
     * Actualiza un gasto.
     *
     * @param int    $idgasto   Identificador del gasto.
     * @param int    $idusuario Usuario responsable.
     * @param string $fecha     Fecha.
     * @param string $concepto  Descripción.
     * @param float  $gasto     Valor.
     * @return bool
     */
    public function editar($idgasto, $idusuario, $fecha, $concepto, $gasto)
    {
        $sql = "UPDATE gastos
                SET idusuario = ?, fecha = ?, concepto = ?, gasto = ?
                WHERE idgasto = ?";
        return Consulta::ejecutar($sql, [$idusuario, $fecha, $concepto, $gasto, $idgasto]);
    }

    /**
     * Elimina un gasto.
     *
     * @param int $idgasto Identificador del gasto.
     * @return bool
     */
    public function eliminar($idgasto)
    {
        return Consulta::ejecutar("DELETE FROM gastos WHERE idgasto = ?", [$idgasto]);
    }

    /**
     * Devuelve un gasto para editarlo.
     *
     * @param int $idgasto Identificador del gasto.
     * @return array|null
     */
    public function mostrar($idgasto)
    {
        return Consulta::fila("SELECT * FROM gastos WHERE idgasto = ?", [$idgasto]);
    }

    /**
     * Lista los gastos con el nombre del responsable.
     *
     * @return Resultado
     */
    public function listar()
    {
        $sql = "SELECT g.idgasto, g.idusuario, u.nombre AS Usuario, g.fecha, g.concepto, g.gasto
                FROM gastos g
                INNER JOIN usuarios u ON g.idusuario = u.idusuario";
        return Consulta::listar($sql);
    }
}
