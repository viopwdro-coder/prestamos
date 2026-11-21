<?php
/**
 * Modelo de pagos: acceso a la tabla `pagos`.
 *
 * @package Prestamos\Modelos
 */
require_once __DIR__ . "/../config/Database.php";

/**
 * Operaciones sobre las cuotas pagadas por préstamo (RF-03).
 *
 * Nota: el nombre de la clase se mantiene como Pago porque ajax/pagos.php
 * la instancia con ese nombre.
 */
class Pago
{
    /**
     * Constructor vacío. Corrige el error del original (`__contruct`), que
     * PHP nunca ejecutaba.
     */
    public function __construct()
    {
    }

    /**
     * Registra un pago de cuota.
     *
     * @param int    $idprestamo Préstamo al que corresponde el pago.
     * @param string $usuario    Nombre del usuario que recibe el pago.
     * @param string $fecha      Fecha del pago.
     * @param float  $cuota      Valor pagado; debe ser mayor que cero.
     * @return bool
     */
    public function insertar($idprestamo, $usuario, $fecha, $cuota)
    {
        $sql = "INSERT INTO pagos (idprestamo, usuario, fecha, cuota) VALUES (?, ?, ?, ?)";
        return Consulta::ejecutar($sql, [$idprestamo, $usuario, $fecha, $cuota]);
    }

    /**
     * Actualiza un pago existente.
     *
     * @param int    $idpago     Identificador del pago.
     * @param int    $idprestamo Préstamo.
     * @param string $usuario    Usuario.
     * @param string $fecha      Fecha.
     * @param float  $cuota      Valor.
     * @return bool
     */
    public function editar($idpago, $idprestamo, $usuario, $fecha, $cuota)
    {
        $sql = "UPDATE pagos
                SET idprestamo = ?, usuario = ?, fecha = ?, cuota = ?
                WHERE idpago = ?";
        return Consulta::ejecutar($sql, [$idprestamo, $usuario, $fecha, $cuota, $idpago]);
    }

    /**
     * Elimina un pago.
     *
     * @param int $idpago Identificador del pago.
     * @return bool
     */
    public function eliminar($idpago)
    {
        return Consulta::ejecutar("DELETE FROM pagos WHERE idpago = ?", [$idpago]);
    }

    /**
     * Devuelve un pago para editarlo.
     *
     * Corrige el original, que no tenía WHERE y devolvía el primer registro
     * de la tabla.
     *
     * @param int $idpago Identificador del pago.
     * @return array|null
     */
    public function mostrar($idpago)
    {
        $sql = "SELECT idpago, idprestamo, usuario, fecha, cuota
                FROM pagos
                WHERE idpago = ?";
        return Consulta::fila($sql, [$idpago]);
    }

    /**
     * Lista los pagos con el nombre del cliente.
     *
     * @return Resultado
     */
    public function listar()
    {
        $sql = "SELECT g.idpago, c.nombre AS cliente, g.usuario, g.fecha, g.cuota
                FROM pagos g
                INNER JOIN prestamos p ON g.idprestamo = p.idprestamo
                INNER JOIN clientes c ON p.idcliente = c.idcliente";
        return Consulta::listar($sql);
    }
}
