<?php
/**
 * Modelo de préstamos: acceso a la tabla `prestamos`.
 *
 * @package Prestamos\Modelos
 */
require_once __DIR__ . "/../config/Database.php";

/**
 * Operaciones sobre los préstamos asociados a un cliente (RF-02, RF-04).
 */
class Prestamo
{
    /**
     * Constructor vacío; se mantiene por compatibilidad con ajax/.
     */
    public function __construct()
    {
    }

    /**
     * Registra un préstamo nuevo y activo.
     *
     * @param int    $idcliente   Cliente que recibe el préstamo.
     * @param int    $usuario     Usuario que lo registra.
     * @param string $fprestamo   Fecha del préstamo (Y-m-d).
     * @param float  $monto       Monto prestado; debe ser mayor que cero.
     * @param float  $interes     Interés del préstamo.
     * @param float  $saldo       Saldo inicial.
     * @param string $formapago   Forma de pago.
     * @param string $fechapago   Fecha de pago.
     * @param string $plazo       Plazo.
     * @param string $fplazo      Fecha límite.
     * @return bool true si se guardó.
     */
    public function insertar($idcliente, $usuario, $fprestamo, $monto, $interes, $saldo, $formapago, $fechapago, $plazo, $fplazo)
    {
        $sql = "INSERT INTO prestamos
                (idcliente, usuario, fprestamo, monto, interes, saldo, formapago, fpago, plazo, fplazo, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        return Consulta::ejecutar($sql, [
            $idcliente, $usuario, $fprestamo, $monto, $interes, $saldo,
            $formapago, $fechapago, $plazo, $fplazo,
        ]);
    }

    /**
     * Actualiza un préstamo existente.
     *
     * @param int    $idprestamo  Identificador del préstamo.
     * @param int    $idcliente   Cliente.
     * @param int    $usuario     Usuario que registra.
     * @param string $fprestamo   Fecha del préstamo.
     * @param float  $monto       Monto.
     * @param float  $interes     Interés.
     * @param float  $saldo       Saldo.
     * @param string $formapago   Forma de pago.
     * @param string $fechapago   Fecha de pago.
     * @param string $plazo       Plazo.
     * @param string $fplazo      Fecha límite.
     * @return bool true si se actualizó.
     */
    public function editar($idprestamo, $idcliente, $usuario, $fprestamo, $monto, $interes, $saldo, $formapago, $fechapago, $plazo, $fplazo)
    {
        $sql = "UPDATE prestamos
                SET idcliente = ?, usuario = ?, fprestamo = ?, monto = ?, interes = ?,
                    saldo = ?, formapago = ?, fpago = ?, plazo = ?, fplazo = ?
                WHERE idprestamo = ?";
        return Consulta::ejecutar($sql, [
            $idcliente, $usuario, $fprestamo, $monto, $interes, $saldo,
            $formapago, $fechapago, $plazo, $fplazo, $idprestamo,
        ]);
    }

    /**
     * Elimina un préstamo.
     *
     * @param int $idprestamo Identificador del préstamo.
     * @return bool
     */
    public function eliminar($idprestamo)
    {
        return Consulta::ejecutar("DELETE FROM prestamos WHERE idprestamo = ?", [$idprestamo]);
    }

    /**
     * Marca como cancelado (estado 0) un préstamo cuyo saldo es cero.
     *
     * Solo afecta al préstamo indicado y únicamente si su saldo es 0.
     *
     * @param int $idprestamo Identificador del préstamo.
     * @return bool
     */
    public function cancelado($idprestamo)
    {
        return Consulta::ejecutar(
            "UPDATE prestamos SET estado = 0 WHERE idprestamo = ? AND saldo = 0",
            [$idprestamo]
        );
    }

    /**
     * Devuelve un préstamo con los nombres de su cliente y usuario.
     *
     * @param int $idprestamo Identificador del préstamo.
     * @return array|null Fila del préstamo o null si no existe.
     */
    public function mostrar($idprestamo)
    {
        $sql = "SELECT p.idprestamo, c.nombre AS cliente, u.nombre AS usuario,
                       DATE(p.fprestamo) AS fecha, p.monto, p.interes, p.saldo,
                       p.formapago, DATE(p.fpago) AS fechap, p.plazo,
                       DATE(p.fplazo) AS fechaf, p.estado
                FROM prestamos p
                INNER JOIN clientes c ON p.idcliente = c.idcliente
                INNER JOIN usuarios u ON p.usuario = u.idusuario
                WHERE p.idprestamo = ?";
        return Consulta::fila($sql, [$idprestamo]);
    }

    /**
     * Lista todos los préstamos con su cliente y usuario.
     *
     * @return Resultado
     */
    public function listar()
    {
        $sql = "SELECT p.idprestamo, c.nombre AS cliente, u.nombre AS usuario,
                       DATE(p.fprestamo) AS fecha, p.monto, p.interes, p.saldo,
                       p.formapago, DATE(p.fpago) AS fechap, p.plazo,
                       DATE(p.fplazo) AS fechaf, p.estado
                FROM prestamos p
                INNER JOIN clientes c ON p.idcliente = c.idcliente
                INNER JOIN usuarios u ON p.usuario = u.idusuario";
        return Consulta::listar($sql);
    }

    /**
     * Lista los préstamos activos para una lista desplegable.
     *
     * @return Resultado
     */
    public function select()
    {
        $sql = "SELECT p.idprestamo, c.nombre
                FROM prestamos p
                INNER JOIN clientes c ON p.idcliente = c.idcliente
                WHERE p.estado = 1
                ORDER BY c.nombre ASC";
        return Consulta::listar($sql);
    }
}
