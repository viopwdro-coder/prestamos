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
     * @param string $clave      Clave en texto plano; solo se guarda su hash (password_hash).
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
            $pdo->prepare($sql)->execute([$nombre, $direccion, $telefono, $login, password_hash($clave, PASSWORD_DEFAULT), $imagen]);
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
     * @param string $clave      Clave nueva en texto plano; si va vacía se conserva la actual.
     * @param string $imagen     Archivo de foto.
     * @param array  $permisos   Permisos nuevos.
     * @return bool true si todo se guardó.
     */
    public function editar($idusuario, $nombre, $direccion, $telefono, $login, $clave, $imagen, $permisos)
    {
        $pdo = Database::conectar();
        try {
            $pdo->beginTransaction();

            if ($clave === "" || $clave === null) {
                // Sin clave nueva: se conserva la que ya tiene
                $sql = "UPDATE usuarios
                        SET nombre = ?, direccion = ?, telefono = ?, login = ?, imagen = ?
                        WHERE idusuario = ?";
                $pdo->prepare($sql)->execute([$nombre, $direccion, $telefono, $login, $imagen, $idusuario]);
            } else {
                $sql = "UPDATE usuarios
                        SET nombre = ?, direccion = ?, telefono = ?, login = ?, clave = ?, imagen = ?
                        WHERE idusuario = ?";
                $pdo->prepare($sql)->execute([$nombre, $direccion, $telefono, $login, password_hash($clave, PASSWORD_DEFAULT), $imagen, $idusuario]);
            }

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
        return Consulta::fila("SELECT idusuario, nombre, direccion, telefono, login, imagen, estado FROM usuarios WHERE idusuario = ?", [$idusuario]);
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
     * Verifica las credenciales de un usuario activo (RF-09, RNF-08).
     *
     * Las claves se guardan con password_hash y se comprueban con password_verify.
     * Las cuentas antiguas guardadas con SHA-256 entran una última vez: en ese
     * primer acceso correcto su clave se convierte a password_hash automáticamente.
     *
     * @param string $login Nombre de acceso.
     * @param string $clave Clave en texto plano, tal como la escribió el usuario.
     * @return Resultado Con los datos del usuario; vacío si las credenciales no son válidas.
     */
    public function verificar($login, $clave)
    {
        $columnas = "idusuario, nombre, direccion, telefono, imagen, login";

        $usuario = Consulta::fila("SELECT idusuario, clave FROM usuarios WHERE login = ? AND estado = 1", [$login]);

        if ($usuario !== null && $this->claveCorrecta($usuario, $clave)) {
            return Consulta::listar("SELECT $columnas FROM usuarios WHERE idusuario = ?", [$usuario["idusuario"]]);
        }

        // Credenciales incorrectas: resultado vacío (fetch_object() devuelve null)
        return Consulta::listar("SELECT $columnas FROM usuarios WHERE 1 = 0");
    }

    /**
     * Compara la clave escrita con la guardada y, si hace falta, actualiza el hash.
     *
     * @param array  $usuario Fila con idusuario y clave guardada.
     * @param string $clave   Clave en texto plano.
     * @return bool true si la clave es correcta.
     */
    private function claveCorrecta(array $usuario, $clave)
    {
        $guardada = (string) $usuario["clave"];

        // Clave guardada con password_hash
        if (!empty(password_get_info($guardada)["algo"])) {
            if (!password_verify($clave, $guardada)) {
                return false;
            }
            if (password_needs_rehash($guardada, PASSWORD_DEFAULT)) {
                $this->guardarHash($usuario["idusuario"], $clave);
            }
            return true;
        }

        // Cuenta antigua guardada con SHA-256 (64 caracteres hexadecimales)
        if (preg_match('/^[a-f0-9]{64}$/i', $guardada) === 1
            && hash_equals(strtolower($guardada), hash("sha256", $clave))) {
            $this->guardarHash($usuario["idusuario"], $clave);
            return true;
        }

        return false;
    }

    /**
     * Guarda el hash (password_hash) de una clave para un usuario.
     *
     * @param int    $idusuario Identificador del usuario.
     * @param string $clave     Clave en texto plano.
     * @return bool
     */
    private function guardarHash($idusuario, $clave)
    {
        return Consulta::ejecutar("UPDATE usuarios SET clave = ? WHERE idusuario = ?", [password_hash($clave, PASSWORD_DEFAULT), $idusuario]);
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
