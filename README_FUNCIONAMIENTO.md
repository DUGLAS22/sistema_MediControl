# 💊 Sistema de Control de Medicamentos y Recordatorios

## 📌 Descripción General
El **Sistema de Control de Medicamentos y Recordatorios** permite a **administradores**, **cuidadores** y **pacientes** gestionar medicamentos, registrar tomas y administrar recordatorios, con control de accesos por roles.

El sistema está desarrollado en **PHP + MySQL** y cuenta con autenticación de usuarios y un panel adaptado según el rol.

---

## 🔐 Accesos de Prueba
- **Administrador** → `duglas2@gmail.com` | **1234**  
- **Cuidador** → `duglas20@gmail.com` | **1234**  
- **Paciente** → `diego12@gmail.com` | **1234**  
- **aqui esta para probar el sistema **: [Demo](http://medicontrol.rf.gd/login.php)  

---

## 🧩 Funcionalidad por Rol

### 👑 Administrador
- **Gestionar Usuarios**: Crear, editar, eliminar.
- **Asignar Pacientes** a cuidadores.
- **Ver Registros (Logs)** de eventos del sistema.
- **Gestionar Medicamentos** para cualquier paciente.
- **Historial de Tomas** global.
- **Registrar Tomas** a cualquier paciente.
- **Reportes y Gráficos** de cumplimiento.
- **Crear Recordatorios** para cualquier paciente.

### 🩺 Cuidador
- **Ver Pacientes Asignados**.
- **Ver Medicamentos** de sus pacientes.
- **Registrar Tomas** a sus pacientes.
- **Historial de Tomas** de sus pacientes.
- **Crear Recordatorios** para sus pacientes.

### 🧑‍🦱 Paciente
- **Gestionar Medicamentos** propios.
- **Registrar Tomas** propias.
- **Historial de Tomas** personales.
- **Crear Recordatorios** propios.
- **Ver Recordatorios Pendientes** asociados a sus medicamentos.

---

## 📊 Reportes Disponibles
- **Tomas por Estado**: Realizadas, Omitidas, Retrasadas.
- **Tomas por Paciente**.
- **Totales y Porcentajes** de cumplimiento.
- **Gráficos Dinámicos** para análisis rápido.

---

## 🖥️ Guía Rápida de Uso

### 1️⃣ Iniciar Sesión
1. Ir a: [http://medicontrol.rf.gd/login.php](http://medicontrol.rf.gd/login.php)  
2. Introducir correo y contraseña.  
3. El sistema mostrará el panel según el rol.

### 2️⃣ Administración (solo Admin)
- **Usuarios** → Agregar, editar o eliminar usuarios.
- **Asignaciones** → Relacionar cuidadores con pacientes.
- **Medicamentos** → Añadir medicamentos a cualquier paciente.
- **Historial** → Ver todas las tomas registradas.
- **Reportes** → Analizar el rendimiento del tratamiento.

### 3️⃣ Cuidador
- **Pacientes** → Lista de pacientes asignados.
- **Medicamentos** → Medicamentos de sus pacientes.
- **Registrar Toma** → Solo para pacientes asignados.
- **Recordatorios** → Crear para sus pacientes.

### 4️⃣ Paciente
- **Mis Medicamentos** → Administrar medicamentos propios.
- **Registrar Toma** → Ingresar cuando se toma un medicamento.
- **Recordatorios Pendientes** → Lista de próximos recordatorios.
- **Historial de Tomas** → Seguimiento personal.

---

## 🛠️ Tecnologías Utilizadas
- **Lenguaje**: PHP 8.x
- **Base de Datos**: MySQL 8.x (utf8mb4_general_ci)
- **Servidor Local**: XAMPP
- **Estilos**: CSS personalizado + HTML5
- **Gráficos**: Chart.js


r al administrador del sistema.
