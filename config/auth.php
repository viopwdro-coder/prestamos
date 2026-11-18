<?php
// Verificación de sesión y permisos para los endpoints de ajax/
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

function responder_acceso_denegado($codigo, $mensaje)
{
	http_response_code($codigo);
	echo $mensaje;
	exit();
}

/**
 * Exige sesión iniciada y, opcionalmente, un permiso de módulo.
 * $permiso es el nombre de la variable de sesión: Clientes, Prestamos, Pagos, Usuarios, Gastos...
 */
function exigir_permiso($permiso = null)
{
	if (!isset($_SESSION['idusuario'])) {
		responder_acceso_denegado(401, "Sesión no iniciada");
	}
	if ($permiso !== null && empty($_SESSION[$permiso])) {
		responder_acceso_denegado(403, "No tiene permiso para esta operación");
	}
}
?>