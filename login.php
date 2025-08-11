<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];

    $sql = "SELECT * FROM usuarios WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        if (password_verify($contrasena, $usuario['contrasena'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['rol'] = $usuario['rol'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Contraseña incorrecta. Por favor, inténtalo de nuevo.";
        }
    } else {
        $error = "No existe una cuenta con este correo electrónico.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediControl - Iniciar Sesión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Estilos específicos para el login */
        .login-container {
            display: flex;
            min-height: 100vh;
            background-color: #f5f7fb;
        }
        
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 5%;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .login-left::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .login-left-content {
            max-width: 500px;
            z-index: 1;
        }
        
        .login-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .login-logo i {
            font-size: 2.5rem;
        }
        
        .login-logo h1 {
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .login-left h2 {
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            line-height: 1.3;
        }
        
        .login-left p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        
        .login-features {
            margin-top: 40px;
        }
        
        .login-feature {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .login-feature i {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .login-feature span {
            font-size: 0.95rem;
            opacity: 0.8;
        }
        
        .login-right {
            width: 450px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background-color: white;
        }
        
        .login-form-container {
            width: 100%;
            max-width: 350px;
        }
        
        .login-form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-form-header h2 {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .login-form-header p {
            color: var(--gray);
            font-size: 0.95rem;
        }
        
        .login-form .form-group {
            margin-bottom: 20px;
        }
        
        .login-form .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 0.95rem;
        }
        
        .login-form .input-with-icon {
            position: relative;
        }
        
        .login-form .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 1rem;
        }
        
        .login-form input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: var(--transition);
        }
        
        .login-form input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }
        
        .login-form .forgot-password {
            display: block;
            text-align: right;
            font-size: 0.85rem;
            color: var(--primary);
            margin-top: 5px;
            text-decoration: none;
        }
        
        .login-form .forgot-password:hover {
            text-decoration: underline;
        }
        
        .login-form button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
        }
        
        .login-form button:hover {
            background-color: #3a56d4;
        }
        
        .login-form-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 0.95rem;
            color: var(--gray);
        }
        
        .login-form-footer a {
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
        }
        
        .login-form-footer a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: var(--danger);
            background-color: rgba(247, 37, 133, 0.1);
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error-message i {
            font-size: 1.1rem;
        }
        
        @media (max-width: 992px) {
            .login-left {
                display: none;
            }
            
            .login-right {
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .login-right {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Sección izquierda con información -->
        <div class="login-left">
            <div class="login-left-content">
                <div class="login-logo">
                    <i class="fas fa-pills"></i>
                    <h1>MediControl</h1>
                </div>
                <h2>Control y seguimiento de medicamentos</h2>
                <p>Gestiona tus medicamentos, registra tomas y recibe recordatorios en un solo lugar.</p>
                
                <div class="login-features">
                    <div class="login-feature">
                        <i class="fas fa-bell"></i>
                        <span>Recordatorios inteligentes para tus medicamentos</span>
                    </div>
                    <div class="login-feature">
                        <i class="fas fa-chart-line"></i>
                        <span>Seguimiento detallado de tu historial de tomas</span>
                    </div>
                    <div class="login-feature">
                        <i class="fas fa-shield-alt"></i>
                        <span>Seguridad y privacidad de tus datos</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sección derecha con formulario -->
        <div class="login-right">
            <div class="login-form-container">
                <div class="login-form-header">
                    <h2>Iniciar Sesión</h2>
                    <p>Ingresa tus credenciales para acceder a tu cuenta</p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                
                <form class="login-form" method="POST">
                    <div class="form-group">
                        <label for="correo">Correo electrónico</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="correo" name="correo" placeholder="tucorreo@ejemplo.com" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="contrasena">Contraseña</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="contrasena" name="contrasena" placeholder="••••••••" required>
                        </div>
                        <a href="forgot-password.php" class="forgot-password">¿Olvidaste tu contraseña?</a>
                    </div>
                    
                    <button type="submit">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </button>
                </form>
                
                <div class="login-form-footer">
                    <p>¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>