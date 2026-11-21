<?php
/**
 * Modelo de usuarios: acceso a las tablas `usuarios` y `usuariopermiso`.
 *
 * @package Prestamos\Modelos
 */
require_once __DIR__ . "/../config/Database.php";

/**
 * Gestión de usuarios, sus permisos y la verificación de acceso (RF-06, RF-07, RF-09).
 */
class Usuarios
{
    /**
     * Constructor vacío; se mantiene por compatibilidad con ajax/.
     */
    public function __construct()
    {
    }

    /**
     * Crea un usuario y le asigna sus permisos en una sola transacción.
     *
     * Si falla cualquier paso, no se guarda nada.
     *
     * @param string $nombre     Nombre del usuario.
     * @param string $direccion  Dirección.
     * @param string $telefono   Teléfono.
     * @param string $login      Nombre de acceso.
     * @param string $clave      Clave ya cifrada por el controlador.
     * @param string $imagen     Nombre del archivo de foto.
     * @param array  $permisos   Identificadores de permisos a asignar.
     * @return bool true si todo se guardó.
     */
    public function insertar($nombre, $direccion, $telefono, $login, $clave, $imagen, $permisos)
    {
        $pdo = Database::conectar();
        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO usuarios (nombre, direccion, telefono, login, clave, imagen, estado)
                    VALUES (?, ?, ?, ?, ?, ?, 1)";
            $pdo->prepare($sql)->execute([$nombre, $direccion, $telefono, $login, $clave, $imagen]);
            $idusuario = (int) $pdo->lastInsertId();

            $this->asignarPermisos($pdo, $idusuario, $permisos);

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error al insertar usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un usuario y reemplaza sus permisos en una transacción.
     *
     * @param int    $idusuario  Identificador del usuario.
     * @param string $nombre     Nombre.
     * @param string $direccion  Dirección.
     * @param string $telefono   Teléfono.
     * @param string $login      Nombre de acceso.
     * @param string $clave      Clave ya cifrada por el controlador.
     * @param string $imagen     Archivo de foto.
     * @param array  $permisos   Permisos nuevos.
     * @return bool true si todo se guardó.
     */
    public function editar($idusuario, $nombre, $direccion, $telefono, $login, $clave, $imagen, $permisos)
    {
        $pdo = Database::conectar();
        try {
            $pdo->beginTransaction();

            $sql = "UPDATE usuarios
                    SET nombre = ?, direccion = ?, telefono = ?, login = ?, clave = ?, imagen = ?
                    WHERE idusuario = ?";
            $pdo->prepare($sql)->execute([$nombre, $direccion, $telefono, $login, $clave, $imagen, $idusuario]);

            $pdo->prepare("DELETE FROM usuariopermiso WHERE idusuario = ?")->execute([$idusuario]);
            $this->asignarPermisos($pdo, $idusuario, $permisos);

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error al editar usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Inserta la relación usuario-permiso para cada permiso indicado.
     *
     * @param PDO   $pdo        Conexión con la transacción abierta.
     * @param int   $idusuario  Usuario.
     * @param array $permisos   Identificadores de permisos.
     * @return void
     */
    private function asignarPermisos(PDO $pdo, $idusuario, array $permisos)
    {
        $sql = "INSERT INTO usuariopermiso (idusuario, idpermiso) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        foreach ($permisos as $idpermiso) {
            $stmt->execute([$idusuario, $idpermiso]);
        }
    }

    /**
     * Desactiva un usuario (no puede iniciar sesión).
     *
     * @param int $idusuario Identificador del usuario.
     * @return bool
     */
    public function desactivar($idusuario)
    {
        return Consulta::ejecutar("UPDATE usuarios SET estado = 0 WHERE idusuario = ?", [$idusuario]);
    }

    /**
     * Reactiva un usuario.
     *
     * @param int $idusuario Identificador del usuario.
     * @return bool
     */
    public function activar($idusuario)
    {
        return Consulta::ejecutar("UPDATE usuarios SET estado = 1 WHERE idusuario = ?", [$idusuario]);
    }

    /**
     * Devuelve un usuario para editarlo.
     *
     * @param int $idusuario Identificador del usuario.
     * @return array|null
     */
    public function mostrar($idusuario)
    {
        return Consulta::fila("SELECT * FROM usuarios WHERE idusuario = ?", [$idusuario]);
    }

    /**
     * Lista todos los usuarios.
     *
     * @return Resultado
     */
    public function listar()
    {
        return Consulta::listar("SELECT * FROM usuarios");
    }

    /**
     * Devuelve los permisos asignados a un usuario.
     *
     * @param int $idusuario Identificador del usuario.
     * @return Resultado
     */
    public function listarmarcados($idusuario)
    {
        return Consulta::listar("SELECT * FROM usuariopermiso WHERE idusuario = ?", [$idusuario]);
    }

    /**
     * Verifica las credenciales de un usuario activo.
     *
     * Usa consulta preparada. La clave llega ya convertida a hash SHA-256
     * desde ajax/usuarios.php. Pendiente de la tarea B3: cambiar a
     * password_verify() con hash de password_hash().
     *
     * @param string $login Nombre de acceso.
     * @param string $clave Hash de la clave.
     * @return Resultado Vacío si las credenciales no son válidas.
     */
    public function verificar($login, $clave)
    {
        $sql = "SELECT idusuario, nombre, direccion, telefono, imagen, login
                FROM usuarios
                WHERE login = ? AND clave = ? AND estado = 1";
        return Consulta::listar($sql, [$login, $clave]);
    }

    /**
     * Lista los usuarios activos para una lista desplegable.
     *
     * @return Resultado
     */
    public function select()
    {
        return Consulta::listar("SELECT * FROM usuarios WHERE estado = 1 ORDER BY nombre DESC");
    }
}
