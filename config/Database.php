<?php
/**
 * Acceso a la base de datos con PDO y sentencias preparadas.
 *
 * Reemplaza las funciones de config/Conexion.php. Todas las consultas del
 * sistema deben pasar los valores como parámetros (?) y nunca concatenarlos
 * en el SQL, para prevenir inyección SQL (RNF-08).
 *
 * @package Prestamos\Config
 */

require_once __DIR__ . "/global.php";

/**
 * Limpia un valor recibido por POST antes de usarlo.
 *
 * Se conserva con el mismo nombre que en config/Conexion.php porque los
 * archivos de ajax/ la llaman directamente. Los valores se guardan con
 * consultas preparadas, así que aquí solo se quitan espacios y se escapan
 * los caracteres HTML.
 *
 * @param string $str Valor recibido.
 * @return string Valor limpio.
 */
if (!function_exists("limpiarCadena")) {
    function limpiarCadena($str)
    {
        if (!isset($str)) {
            return "";
        }
        return htmlspecialchars(trim((string) $str), ENT_QUOTES, "UTF-8");
    }
}

/**
 * Conexión única a MySQL (patrón singleton).
 */
final class Database
{
    /** @var PDO|null Conexión compartida por toda la aplicación. */
    private static $pdo = null;

    /**
     * Devuelve la conexión PDO. La crea la primera vez que se llama.
     *
     * @return PDO Conexión configurada para lanzar excepciones.
     * @throws PDOException Si no se puede conectar a la base de datos.
     */
    public static function conectar(): PDO
    {
        if (self::$pdo === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            self::$pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$pdo;
    }
}

/**
 * Operaciones de consulta con parámetros.
 */
final class Consulta
{
    /**
     * Ejecuta INSERT, UPDATE o DELETE.
     *
     * @param string $sql    Sentencia con marcadores ?.
     * @param array  $params Valores en el mismo orden que los marcadores.
     * @return bool true si se ejecutó; false si hubo error (se registra en el log).
     */
    public static function ejecutar(string $sql, array $params = []): bool
    {
        try {
            $stmt = Database::conectar()->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error en consulta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecuta un SELECT que devuelve varias filas.
     *
     * @param string $sql    Sentencia SELECT con marcadores ?.
     * @param array  $params Valores de los marcadores.
     * @return Resultado Iterable con fetch_object().
     */
    public static function listar(string $sql, array $params = []): Resultado
    {
        $stmt = Database::conectar()->prepare($sql);
        $stmt->execute($params);
        return new Resultado($stmt);
    }

    /**
     * Ejecuta un SELECT y devuelve solo la primera fila.
     *
     * @param string $sql    Sentencia SELECT con marcadores ?.
     * @param array  $params Valores de los marcadores.
     * @return array|null Fila asociativa, o null si no hay resultados.
     */
    public static function fila(string $sql, array $params = []): ?array
    {
        $stmt = Database::conectar()->prepare($sql);
        $stmt->execute($params);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    /**
     * Ejecuta un INSERT y devuelve el identificador generado.
     *
     * @param string $sql    Sentencia INSERT con marcadores ?.
     * @param array  $params Valores de los marcadores.
     * @return int Identificador autoincremental de la última fila insertada.
     */
    public static function insertarYObtenerId(string $sql, array $params = []): int
    {
        $pdo = Database::conectar();
        $pdo->prepare($sql)->execute($params);
        return (int) $pdo->lastInsertId();
    }
}

/**
 * Envoltorio de un resultado SELECT.
 *
 * Mantiene el método fetch_object() que usan los archivos de ajax/, para
 * no cambiar esos archivos en esta fase.
 */
final class Resultado
{
    /** @var PDOStatement */
    private $stmt;

    /**
     * @param PDOStatement $stmt Sentencia ya ejecutada.
     */
    public function __construct(PDOStatement $stmt)
    {
        $this->stmt = $stmt;
    }

    /**
     * Devuelve la siguiente fila como objeto, o null al terminar.
     *
     * Devuelve null (y no false) para que las comparaciones de ajax/ se
     * comporten igual que con mysqli.
     *
     * @return object|null
     */
    public function fetch_object()
    {
        $objeto = $this->stmt->fetchObject();
        return $objeto === false ? null : $objeto;
    }
}
