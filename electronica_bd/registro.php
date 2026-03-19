<?php
session_start();
require_once 'conexion.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registro'])) {
    // Recoger y sanitizar datos
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $apellido_paterno = mysqli_real_escape_string($conn, $_POST['apellido_paterno']);
    $apellido_materno = mysqli_real_escape_string($conn, $_POST['apellido_materno']);
    $direccion = mysqli_real_escape_string($conn, $_POST['direccion']);
    $telefono = mysqli_real_escape_string($conn, $_POST['telefono']);
    $nombre_usuario = mysqli_real_escape_string($conn, $_POST['nombre_usuario']);
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    
    // Validaciones
    if ($contrasena !== $confirmar_contrasena) {
        $error = "Las contraseñas no coinciden";
    } elseif (strlen($contrasena) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } else {
        // Verificar si el usuario ya existe
        $check = mysqli_query($conn, "SELECT id FROM usuarios WHERE nombre_usuario = '$nombre_usuario'");
        if (mysqli_num_rows($check) > 0) {
            $error = "El nombre de usuario ya está registrado";
        } else {
            // Insertar nuevo usuario (por defecto como vendedor)
            $contrasena_hash = hash('sha256', $contrasena);
            $query = "INSERT INTO usuarios (nombre, apellido_paterno, apellido_materno, direccion, telefono, nombre_usuario, contrasena, rol) 
                      VALUES ('$nombre', '$apellido_paterno', '$apellido_materno', '$direccion', '$telefono', '$nombre_usuario', '$contrasena_hash', 'vendedor')";
            
            if (mysqli_query($conn, $query)) {
                $mensaje = "Registro exitoso. Ya puedes iniciar sesión.";
            } else {
                $error = "Error al registrar: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoViral - Registro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            width: 90%;
            max-width: 600px;
            padding: 40px 30px;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .logo {
            width: 200px;
            height: auto;
            border-radius: 10px;
        }
        
        h1 {
            text-align: center;
            color: #000;
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        h1 span {
            color: #0066cc;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .input-group {
            margin-bottom: 15px;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .input-group input,
        .input-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .input-group input:focus,
        .input-group textarea:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }
        
        .input-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn-registro {
            width: 100%;
            padding: 14px;
            background: #0066cc;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin: 20px 0 15px;
        }
        
        .btn-registro:hover {
            background: #0052a3;
        }
        
        .mensaje {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: #0066cc;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .login-link a:hover {
            color: #0052a3;
            text-decoration: underline;
        }
        
        .required-field::after {
            content: " *";
            color: #c62828;
        }
        
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="electronica_bd/logoe.jpeg" alt="TecnoViral Logo" class="logo" onerror="this.src='https://via.placeholder.com/100x100?text=TecnoViral'">
        </div>
        
        <h1>TECNO<span>VIRAL</span></h1>
        <div class="subtitle">Registro de Nuevo Usuario</div>
        
        <?php if ($mensaje): ?>
            <div class="mensaje"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registroForm">
            <div class="form-row">
                <div class="input-group">
                    <label for="nombre" class="required-field">Nombre</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Ingrese su nombre" required>
                </div>
                
                <div class="input-group">
                    <label for="apellido_paterno" class="required-field">Apellido Paterno</label>
                    <input type="text" id="apellido_paterno" name="apellido_paterno" placeholder="Ingrese su apellido paterno" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="input-group">
                    <label for="apellido_materno">Apellido Materno</label>
                    <input type="text" id="apellido_materno" name="apellido_materno" placeholder="Ingrese su apellido materno">
                </div>
                
                <div class="input-group">
                    <label for="telefono" class="required-field">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" placeholder="Ingrese su teléfono" required>
                </div>
            </div>
            
            <div class="input-group">
                <label for="direccion" class="required-field">Dirección</label>
                <textarea id="direccion" name="direccion" placeholder="Ingrese su dirección completa" required></textarea>
            </div>
            
            <div class="form-row">
                <div class="input-group">
                    <label for="nombre_usuario" class="required-field">Nombre de Usuario</label>
                    <input type="text" id="nombre_usuario" name="nombre_usuario" placeholder="Cree su nombre de usuario" required>
                </div>
                
                <div class="input-group">
                    <label for="contrasena" class="required-field">Contraseña</label>
                    <input type="password" id="contrasena" name="contrasena" placeholder="Mínimo 6 caracteres" required>
                </div>
            </div>
            
            <div class="input-group">
                <label for="confirmar_contrasena" class="required-field">Confirmar Contraseña</label>
                <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" placeholder="Repita su contraseña" required>
            </div>
            
            <button type="submit" name="registro" class="btn-registro">SIGN UP</button>
        </form>
        
        <div class="login-link">
            <a href="login.php">¿Ya tienes cuenta? Inicia Sesión</a>
        </div>
    </div>
</body>
</html>