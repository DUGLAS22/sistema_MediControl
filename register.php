<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];

    $sql = "INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $correo, $contrasena, $rol);

    if ($stmt->execute()) {
        header("Location: login.php");
        exit;
    } else {
        $error = "Error al registrar usuario. Por favor, inténtalo de nuevo.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediControl - Registro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Estilos específicos para el registro */
        .register-container {
            display: flex;
            min-height: 100vh;
            background-color: #f5f7fb;
        }
        
        .register-left {
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
        
        .register-left::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .register-left::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .register-left-content {
            max-width: 500px;
            z-index: 1;
        }
        
        .register-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .register-logo i {
            font-size: 2.5rem;
        }
        
        .register-logo h1 {
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .register-left h2 {
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            line-height: 1.3;
        }
        
        .register-left p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        
        .register-features {
            margin-top: 40px;
        }
        
        .register-feature {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .register-feature i {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .register-feature span {
            font-size: 0.95rem;
            opacity: 0.8;
        }
        
        .register-right {
            width: 450px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background-color: white;
        }
        
        .register-form-container {
            width: 100%;
            max-width: 350px;
        }
        
        .register-form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-form-header h2 {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .register-form-header p {
            color: var(--gray);
            font-size: 0.95rem;
        }
        
        .register-form .form-group {
            margin-bottom: 20px;
        }
        
        .register-form .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 0.95rem;
        }
        
        .register-form .input-with-icon {
            position: relative;
        }
        
        .register-form .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 1rem;
        }
        
        .register-form input,
        .register-form select {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: var(--transition);
            appearance: none;
            background-color: white;
        }
        
        .register-form select {
            padding: 12px 15px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px 12px;
        }
        
        .register-form input:focus,
        .register-form select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }
        
        .password-strength {
            margin-top: 5px;
            height: 4px;
            background-color: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            background-color: #dc3545;
            transition: width 0.3s ease;
        }
        
        .register-form button {
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .register-form button:hover {
            background-color: #3a56d4;
        }
        
        .register-form-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 0.95rem;
            color: var(--gray);
        }
        
        .register-form-footer a {
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
        }
        
        .register-form-footer a:hover {
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
            .register-left {
                display: none;
            }
            
            .register-right {
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .register-right {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Sección izquierda con información -->
        <div class="register-left">
            <div class="register-left-content">
                <div class="register-logo">
                    <i class="fas fa-pills"></i>
                    <h1>MediControl</h1>
                </div>
                <h2>Comienza a gestionar tus medicamentos</h2>
                <p>Regístrate ahora para acceder a todas las funciones de seguimiento y control de medicamentos.</p>
                
                <div class="register-features">
                    <div class="register-feature">
                        <i class="fas fa-user-shield"></i>
                        <span>Diferentes roles según tus necesidades</span>
                    </div>
                    <div class="register-feature">
                        <i class="fas fa-clock"></i>
                        <span>Recordatorios personalizados</span>
                    </div>
                    <div class="register-feature">
                        <i class="fas fa-chart-pie"></i>
                        <span>Reportes y estadísticas detalladas</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sección derecha con formulario -->
        <div class="register-right">
            <div class="register-form-container">
                <div class="register-form-header">
                    <h2>Crear Cuenta</h2>
                    <p>Completa el formulario para registrarte</p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                
                <form class="register-form" method="POST">
                    <div class="form-group">
                        <label for="nombre">Nombre completo</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="nombre" name="nombre" placeholder="Ej: Juan Pérez" required>
                        </div>
                    </div>
                    
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
                        <div class="password-strength">
                            <div class="password-strength-bar" id="password-strength-bar"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="rol">Tipo de cuenta</label>
                        <select id="rol" name="rol" required>
                            <option value="" disabled selected>Selecciona tu rol</option>
                            <option value="paciente">Paciente</option>
                            <option value="cuidador">Cuidador</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    
                    <button type="submit">
                        <i class="fas fa-user-plus"></i> Registrarse
                    </button>
                </form>
                
                <div class="register-form-footer">
                    <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Validación de fortaleza de contraseña
        const passwordInput = document.getElementById('contrasena');
        const strengthBar = document.getElementById('password-strength-bar');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Verificar longitud
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Verificar caracteres especiales
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 1;
            
            // Verificar números
            if (/\d/.test(password)) strength += 1;
            
            // Verificar mayúsculas y minúsculas
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 1;
            
            // Actualizar barra de fortaleza
            switch(strength) {
                case 0:
                    strengthBar.style.width = '0%';
                    strengthBar.style.backgroundColor = '#dc3545';
                    break;
                case 1:
                    strengthBar.style.width = '20%';
                    strengthBar.style.backgroundColor = '#dc3545';
                    break;
                case 2:
                    strengthBar.style.width = '40%';
                    strengthBar.style.backgroundColor = '#fd7e14';
                    break;
                case 3:
                    strengthBar.style.width = '60%';
                    strengthBar.style.backgroundColor = '#ffc107';
                    break;
                case 4:
                    strengthBar.style.width = '80%';
                    strengthBar.style.backgroundColor = '#28a745';
                    break;
                case 5:
                    strengthBar.style.width = '100%';
                    strengthBar.style.backgroundColor = '#28a745';
                    break;
            }
        });
    </script>
</body>
</html>