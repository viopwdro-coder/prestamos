<?php
/**
 * Consultas de resumen para el escritorio.
 *
 * Se eliminaron los métodos que consultaban tablas de otro proyecto
 * (venta, persona, articulo, ingreso). Solo quedan consultas sobre las
 * tablas de este sistema: prestamos, pagos y gastos.
 *
 * @package Prestamos\Modelos
 */
require_once __DIR__ . "/../config/Database.php";

/**
 * Indicadores y reportes del escritorio (RF-08).
 */
class Consultas
{
    /**
     * Constructor vacío; se mantiene por compatibilidad con ajax/.
     */
    public function __construct()
    {
    }

    /**
     * Suma de los montos prestados hoy.
     *
     * @return Resultado Una fila con la clave total_montos.
     */
    public function totalmontohoy()
    {
        $sql = "SELECT IFNULL(SUM(monto), 0) AS total_montos
                FROM prestamos
                WHERE DATE(fprestamo) = CURDATE()";
        return Consulta::listar($sql);
    }

    /**
     * Suma de las cuotas cobradas hoy.
     *
     * @return Resultado Una fila con la clave total_pagos.
     */
    public function totalpagoshoy()
    {
        $sql = "SELECT IFNULL(SUM(cuota), 0) AS total_pagos
                FROM pagos
                WHERE DATE(fecha) = CURDATE()";
        return Consulta::listar($sql);
    }

    /**
     * Suma de los gastos registrados hoy.
     *
     * @return Resultado Una fila con la clave total_gasto.
     */
    public function totalgastohoy()
    {
        $sql = "SELECT IFNULL(SUM(gasto), 0) AS total_gasto
                FROM gastos
                WHERE DATE(fecha) = CURDATE()";
        return Consulta::listar($sql);
    }

    /**
     * Cobros agrupados por mes en los últimos 12 meses.
     *
     * Reemplaza la versión original, que consultaba la tabla `venta` de otro
     * proyecto. El nombre se conserva porque ajax/ lo llama con ese nombre.
     *
     * @return Resultado Filas con mes (AAAA-MM) y total.
     */
    public function ventasultimos_12meses()
    {
        $sql = "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, SUM(cuota) AS total
                FROM pagos
                WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(fecha, '%Y-%m')
                ORDER BY mes ASC";
        return Consulta::listar($sql);
    }
}
