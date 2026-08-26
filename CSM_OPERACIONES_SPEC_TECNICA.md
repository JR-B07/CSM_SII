# CSM OPERACIONES - ESPECIFICACIÓN TÉCNICA PARA DESARROLLO

## 📋 RESUMEN DEL PROYECTO

**Empresa:** CSM Operaciones (Servicios Técnicos Industriales)  
**Ubicación:** San Luis de la Paz, Guanajuato, México  
**Empleados:** 14  
**Presupuesto:** $9,745 USD  
**Timeline:** 16 semanas (Entrega: 10 diciembre 2026)  
**Director:** Juan Héctor Morín Torres  
**Desarrollador:** José Ricardo Becerra Covarrubias (DevSistems)  

**Objetivo:** Crear plataforma integral con Laravel, PHP, Blade y Vue.js que incluya página web profesional, sistema de asistencias automatizado, gestión centralizada de documentos, y nómina automática basada en días asistidos.

---

## 🎯 MÓDULOS A DESARROLLAR

### 1. PÁGINA WEB PÚBLICA
- **Descripción:** Sitio profesional que reemplaza página actual desactualizada
- **Funciones:**
  - Homepage con hero section y servicios destacados
  - Subpáginas: Supervisión EHS, Sistemas contra incendios, Electromecánica, Gestión de proyectos
  - Portfolio con galería de proyectos
  - Formulario de contacto (envía email)
  - Blog de tips/noticias (opcional)
  - SEO optimizado
  - Responsive (móvil, tablet, desktop)
- **Tech:** Blade templates + Tailwind CSS (Frontend en Laravel)
- **Performance:** < 2 segundos carga

### 2. SISTEMA DE ASISTENCIAS
- **Descripción:** Control automatizado de entrada/salida de técnicos
- **Funciones:**
  - Check-in/Check-out por QR (con validación)
  - Captura de ubicación (GPS)
  - Captura de foto de verificación
  - Reportes: diario, semanal, mensual
  - Justificantes de faltas
  - Por proyecto asignado
  - Historial personal
- **Tech:** Laravel (backend) + Vue.js (frontend dinámico)
- **Almacenamiento:** MySQL tabla `asistencias`
- **Rutas en Laravel:** `/asistencias/checkin`, `/asistencias/checkout`, `/asistencias/reportes`

### 3. GESTIÓN DE DOCUMENTOS
- **Descripción:** Centralización segura de documentos de proyectos
- **Funciones:**
  - Upload/Download de archivos (máx 50MB/archivo)
  - Organización jerárquica (Por cliente > Proyecto > Tipo)
  - Permisos granulares (lectura, descarga, edición, admin)
  - Versionamiento automático
  - Auditoría de accesos (quién descargó, cuándo)
  - Búsqueda por nombre/cliente/fecha
  - Almacenamiento: 500GB en Hostinger
- **Tech:** Laravel (gestión) + Vue.js (UI) + Blade (para admin)
- **Seguridad:** Cada descarga registra usuario, IP, timestamp

### 4. NÓMINA AUTOMÁTICA ⭐ CRÍTICO
- **Descripción:** Cálculo automático de pagos basado en asistencias
- **Funciones:**
  - **Fórmula:** (Días asistidos × Salario diario) - Descuentos = Total pago
  - Descuentos automáticos:
    - Faltas: -100% del día
    - Retardos: -10-50% (configurable)
    - Permisos justificados: 0 descuento
  - Reportes para contador (Excel, PDF)
  - Portal personal de técnico (ve solo su nómina)
  - Histórico de 12 meses
  - Exportación para sistemas contables
  - Auditoría de cada cálculo
  - Cálculo automático cada noche (Laravel Job/Scheduler)
- **Tech:** Laravel (lógica) + Vue.js (visualización) + PHP (cálculos)
- **Seguridad:** Encriptación AES-256, acceso solo autorizado

### 5. DASHBOARD EJECUTIVO
- **Descripción:** Panel de control para Director/RH
- **Funciones:**
  - **KPIs en tarjetas:** Asistencias hoy, ausencias, documentos, proyectos activos
  - **Gráficos:**
    - Asistencia por día (líneas)
    - Asistencia por departamento (barras)
    - Documentos compartidos (pastel)
  - Mapa de técnicos en campo (tiempo real)
  - Tabla de próximas citas
  - Últimos documentos subidos
  - Reportes exportables (PDF, Excel)
- **Tech:** Vue.js + Chart.js/Apexcharts
- **Datos:** En vivo desde Laravel backend

---

## 🏗️ ARQUITECTURA TÉCNICA

### Stack Tecnológico

**Backend:**
- Laravel 11+ (PHP framework)
- PHP 8.2+
- Blade (motor de templates)
- Eloquent ORM (models)
- Laravel Scheduler (tareas automáticas)

**Frontend:**
- Vue.js 3+ (componentes dinámicos)
- Tailwind CSS (estilos)
- Axios (HTTP requests)
- Vue Router (navegación SPA)
- Chart.js o Apexcharts (gráficos)

**Base de Datos:**
- MySQL 8.0
- Hostinger (hosting)

**Otros:**
- Laravel Passport (OAuth tokens)
- Laravel Queue (procesos en background)
- Mailtrap o SendGrid (emails)

**Seguridad:**
- Encriptación AES-256 (nóminas)
- Bcrypt (contraseñas)
- Laravel CSRF Protection
- Rate Limiting
- 2FA (opcional para admin)

---

## 📊 BASE DE DATOS - SCHEMA

### Tabla: `usuarios`
```sql
id (BIGINT, PK, AUTO_INCREMENT)
nombre (VARCHAR 100)
email (VARCHAR 100, UNIQUE)
rol (ENUM: técnico, supervisor, RH, admin, contador)
departamento (VARCHAR 50)
salario_diario (DECIMAL 10,2)
password (VARCHAR 255, bcrypt)
activo (BOOLEAN, default true)
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
```

### Tabla: `asistencias`
```sql
id (BIGINT, PK, AUTO_INCREMENT)
usuario_id (BIGINT, FK → usuarios)
fecha (DATE)
hora_entrada (TIME)
hora_salida (TIME, nullable)
ubicacion_lat (DECIMAL 10,8)
ubicacion_lng (DECIMAL 10,8)
foto_verificacion (VARCHAR 255, path)
proyecto (VARCHAR 100)
estado (ENUM: presente, ausente, tarde, justificado)
justificante (TEXT, nullable)
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
```

### Tabla: `documentos`
```sql
id (BIGINT, PK, AUTO_INCREMENT)
nombre (VARCHAR 255)
ruta_archivo (VARCHAR 500, path en storage)
tipo (VARCHAR 20, PDF/DOCX/XLSX/JPG/PNG)
tamaño (BIGINT, bytes)
cliente (VARCHAR 100)
proyecto (VARCHAR 100)
usuario_id (BIGINT, FK → usuarios)
version (INT, default 1)
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
```

### Tabla: `permisos_documentos`
```sql
id (BIGINT, PK, AUTO_INCREMENT)
documento_id (BIGINT, FK → documentos)
usuario_id (BIGINT, FK → usuarios)
nivel_permiso (ENUM: lectura, lectura_descarga, edicion, admin)
created_at (TIMESTAMP)
```

### Tabla: `nominas`
```sql
id (BIGINT, PK, AUTO_INCREMENT)
usuario_id (BIGINT, FK → usuarios)
mes (INT, 1-12)
año (INT)
salario_diario (DECIMAL 10,2)
dias_laborales (INT)
dias_asistidos (INT)
descuentos_faltas (DECIMAL 10,2)
descuentos_retardos (DECIMAL 10,2)
total_pago (DECIMAL 10,2, encrypted)
estado (ENUM: calculada, pagada, procesada)
fecha_pago (DATE, nullable)
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
```

### Tabla: `auditoria`
```sql
id (BIGINT, PK, AUTO_INCREMENT)
usuario_id (BIGINT, FK → usuarios)
accion (VARCHAR 50, login/upload/download/cambio_nomina)
tabla_afectada (VARCHAR 50)
registro_id (BIGINT)
detalles (JSON)
ip_address (VARCHAR 45)
created_at (TIMESTAMP)
```

---

## 🔌 RUTAS LARAVEL (Routes)

### routes/web.php - Página Pública

```php
// Página Web Pública
Route::get('/', [HomepageController::class, 'index'])->name('home');
Route::get('/servicios', [ServiciosController::class, 'index'])->name('servicios');
Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio');
Route::get('/blog', [BlogController::class, 'index'])->name('blog');
Route::post('/contacto', [ContactoController::class, 'store'])->name('contacto.store');
```

### routes/web.php - Sistema Interno (Autenticado)

```php
// Autenticación
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');

// Dashboard
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/kpis', [DashboardController::class, 'getKpis'])->name('dashboard.kpis');
    Route::get('/dashboard/graficos', [DashboardController::class, 'getGraficos'])->name('dashboard.graficos');

    // Asistencias
    Route::post('/asistencias/checkin', [AsistenciaController::class, 'checkin'])->name('asistencias.checkin');
    Route::post('/asistencias/checkout', [AsistenciaController::class, 'checkout'])->name('asistencias.checkout');
    Route::get('/asistencias/mi-historial', [AsistenciaController::class, 'miHistorial'])->name('asistencias.historial');
    Route::get('/asistencias/reportes', [AsistenciaController::class, 'reportes'])->name('asistencias.reportes');

    // Documentos
    Route::post('/documentos/upload', [DocumentoController::class, 'upload'])->name('documentos.upload');
    Route::get('/documentos', [DocumentoController::class, 'lista'])->name('documentos.lista');
    Route::get('/documentos/{id}/download', [DocumentoController::class, 'download'])->name('documentos.download');
    Route::patch('/documentos/{id}/permisos', [DocumentoController::class, 'updatePermisos'])->name('documentos.permisos');

    // Nóminas
    Route::get('/nominas/mi-nomina', [NominaController::class, 'miNomina'])->name('nominas.minomina');
    Route::get('/nominas/historico', [NominaController::class, 'historico'])->name('nominas.historico');
    
    // Solo RH/Admin
    Route::middleware('role:RH,admin')->group(function () {
        Route::get('/asistencias', [AsistenciaController::class, 'index'])->name('asistencias.index');
        Route::post('/asistencias/{id}/justificar', [AsistenciaController::class, 'justificar'])->name('asistencias.justificar');
        
        Route::get('/nominas', [NominaController::class, 'index'])->name('nominas.index');
        Route::post('/nominas/calcular', [NominaController::class, 'calcular'])->name('nominas.calcular');
        Route::get('/nominas/reporte-contador', [NominaController::class, 'reporteContador'])->name('nominas.reporte');
    });

    // Solo Admin
    Route::middleware('role:admin')->group(function () {
        Route::resource('usuarios', UsuarioController::class);
        Route::post('/usuarios/{id}/reset-password', [UsuarioController::class, 'resetPassword'])->name('usuarios.reset');
    });
});
```

---

## 🗂️ ESTRUCTURA DE CARPETAS (LARAVEL)

```
csm-operaciones/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── AsistenciaController.php
│   │   │   ├── DocumentoController.php
│   │   │   ├── NominaController.php
│   │   │   ├── HomepageController.php
│   │   │   ├── UsuarioController.php
│   │   │   └── ContactoController.php
│   │   ├── Middleware/
│   │   │   ├── Authenticate.php
│   │   │   ├── CheckRole.php
│   │   │   └── Encrypt.php
│   │   └── Requests/
│   │       ├── StoreNominaRequest.php
│   │       ├── StoreDocumentoRequest.php
│   │       └── StoreAsistenciaRequest.php
│   │
│   ├── Models/
│   │   ├── Usuario.php
│   │   ├── Asistencia.php
│   │   ├── Documento.php
│   │   ├── PermisoDocumento.php
│   │   ├── Nomina.php
│   │   └── Auditoria.php
│   │
│   ├── Services/
│   │   ├── NominaService.php (lógica de cálculo)
│   │   ├── DocumentoService.php (gestión archivos)
│   │   ├── AuditService.php (registro de acciones)
│   │   └── EmailService.php (notificaciones)
│   │
│   ├── Jobs/
│   │   └── CalcularNominasJob.php (tarea nocturnna automática)
│   │
│   └── Traits/
│       ├── Encryptable.php (encriptar/desencriptar)
│       └── Auditable.php (registrar cambios)
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php (layout principal)
│   │   │   └── guest.blade.php (login)
│   │   │
│   │   ├── auth/
│   │   │   └── login.blade.php
│   │   │
│   │   ├── dashboard/
│   │   │   ├── index.blade.php (con Vue component)
│   │   │   ├── components/
│   │   │   │   ├── kpis.blade.php
│   │   │   │   ├── graficos.blade.php
│   │   │   │   └── rescatistas-mapa.blade.php
│   │   │
│   │   ├── asistencias/
│   │   │   ├── checkin.blade.php (Vue component)
│   │   │   ├── historial.blade.php
│   │   │   └── reportes.blade.php (RH)
│   │   │
│   │   ├── documentos/
│   │   │   ├── index.blade.php (árbol de carpetas)
│   │   │   ├── upload.blade.php
│   │   │   └── permisos.blade.php
│   │   │
│   │   ├── nominas/
│   │   │   ├── mi-nomina.blade.php (técnico)
│   │   │   ├── index.blade.php (RH)
│   │   │   └── reporte-contador.blade.php
│   │   │
│   │   ├── web/
│   │   │   ├── homepage.blade.php
│   │   │   ├── servicios.blade.php
│   │   │   ├── portfolio.blade.php
│   │   │   ├── contacto.blade.php
│   │   │   └── blog.blade.php
│   │   │
│   │   └── usuarios/ (solo admin)
│   │       ├── index.blade.php
│   │       └── form.blade.php
│   │
│   ├── js/
│   │   ├── app.js (bootstrap Vue)
│   │   ├── components/
│   │   │   ├── Checkin.vue
│   │   │   ├── Dashboard.vue
│   │   │   ├── MiNomina.vue
│   │   │   ├── DocumentosArbol.vue
│   │   │   ├── Graficos.vue
│   │   │   └── KPIs.vue
│   │   │
│   │   ├── stores/
│   │   │   ├── authStore.js
│   │   │   ├── asistenciasStore.js
│   │   │   └── nominasStore.js
│   │   │
│   │   └── api.js (configuración axios)
│   │
│   └── css/
│       └── app.css (Tailwind)
│
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_create_usuarios_table.php
│   │   ├── 2024_01_02_create_asistencias_table.php
│   │   ├── 2024_01_03_create_documentos_table.php
│   │   ├── 2024_01_04_create_permisos_documentos_table.php
│   │   ├── 2024_01_05_create_nominas_table.php
│   │   └── 2024_01_06_create_auditoria_table.php
│   │
│   ├── seeders/
│   │   ├── UsuarioSeeder.php (crear 14 técnicos)
│   │   └── DatabaseSeeder.php
│   │
│   └── factories/
│       └── UsuarioFactory.php
│
├── routes/
│   ├── web.php (ver abajo)
│   └── api.php (si necesitas API externa)
│
├── config/
│   ├── app.php
│   ├── database.php
│   ├── mail.php
│   └── custom.php (salarios, configuración proyecto)
│
├── storage/
│   ├── app/
│   │   └── documentos/ (archivos subidos)
│   └── logs/
│       └── laravel.log
│
├── tests/
│   ├── Feature/
│   │   ├── NominaTest.php (test cálculo automático)
│   │   ├── AsistenciaTest.php
│   │   └── DocumentoTest.php
│   │
│   └── Unit/
│       └── NominaServiceTest.php
│
├── .env.example
├── composer.json
├── package.json (para Vue + Tailwind)
├── tailwind.config.js
├── vite.config.js
└── README.md
```

---

## 🚀 INSTRUCCIONES DE SETUP

### Instalación Laravel

```bash
# Crear proyecto
composer create-project laravel/laravel csm-operaciones

# Navegar al proyecto
cd csm-operaciones

# Instalar dependencias JS
npm install

# Instalar Vue y Tailwind
npm install vue@next
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p

# Publicar config de auth
php artisan vendor:publish --provider="Illuminate\Auth\AuthServiceProvider"

# Crear archivo .env
cp .env.example .env
php artisan key:generate
```

### Configurar .env

```
APP_NAME="CSM Operaciones"
APP_ENV=production
APP_URL=https://csm-operaciones.com.mx

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=csm_operaciones
DB_USERNAME=csm_user
DB_PASSWORD=tu_password_seguro

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io (o SendGrid)
MAIL_PORT=465
MAIL_USERNAME=tu_usuario
MAIL_PASSWORD=tu_password

APP_ENCRYPTION=AES-256-CBC
APP_CIPHER=AES-256-CBC
```

### Crear Base de Datos

```bash
# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders (crear usuarios de prueba)
php artisan db:seed

# O específico:
php artisan migrate --seed
```

### Desarrollo Local

```bash
# Terminal 1: Laravel dev server
php artisan serve

# Terminal 2: Compilar Vue + Tailwind
npm run dev

# Terminal 3: Laravel Scheduler (cada minuto)
php artisan schedule:work
```

### Build para Producción

```bash
# Compilar assets
npm run build

# Optimizar Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Deploy a Hostinger (vía Git o FTP)
```

---

## 🔒 REQUERIMIENTOS DE SEGURIDAD

### Autenticación
- Laravel Auth (sesiones + contraseñas)
- Contraseñas con bcrypt (automático en Laravel)
- Middleware `auth` en rutas protegidas
- Middleware `role` para autorización

### Autorización
- Permisos por rol: técnico, supervisor, RH, admin, contador
- Gate y Policy (Laravel)
- Técnico solo ve su nómina/asistencia
- RH ve todo del personal
- Contador solo descarga reportes

### Encriptación
- Contraseñas: bcrypt (Laravel Auth)
- Nóminas en BD: AES-256 (Trait Encryptable)
- HTTPS obligatorio en producción
- Config APP_CIPHER=AES-256-CBC

### CSRF Protection
- Laravel incluye _token automáticamente
- Verificación en todos los formularios

### Auditoría
- Trait Auditable registra: usuario, acción, cambios
- Tabla auditoria guarda: login, upload, download, cambio nómina
- IP address en cada acción
- Timestamp automático

### Cumplimiento Legal
- LFPDPPP: Datos de empleados protegidos
- Retención 7 años: Nóminas guardadas (usar soft deletes)
- Consentimiento: Aceptar términos en login

---

## 📝 COMPONENTES VUE PRINCIPALES

### 1. Checkin.vue
```vue
<template>
  <div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">Registrar Asistencia</h1>
    
    <div class="mb-4">
      <label>Proyecto:</label>
      <select v-model="proyecto" class="w-full p-2 border">
        <option>Seleccionar proyecto</option>
        <option v-for="p in proyectos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
      </select>
    </div>

    <div class="text-center mb-4">
      <button @click="abrirCamara" class="bg-blue-500 text-white p-3 rounded">
        📷 Escanear QR
      </button>
      <video ref="qrVideo" style="width: 300px; height: 300px;"></video>
    </div>

    <div class="mb-4">
      <label>O código manual:</label>
      <input v-model="codigoManual" type="text" class="w-full p-2 border" placeholder="Ingresar código">
    </div>

    <button @click="confirmarEntrada" class="bg-green-500 text-white w-full p-3 rounded font-bold">
      CONFIRMAR ENTRADA
    </button>

    <div v-if="ultimaAsistencia" class="mt-4 bg-gray-100 p-4 rounded">
      ✓ Última entrada: {{ ultimaAsistencia.horaEntrada }}
      ✓ Última salida: {{ ultimaAsistencia.horaSalida }}
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import axios from 'axios'

export default {
  setup() {
    const proyecto = ref('')
    const codigoManual = ref('')
    const ultimaAsistencia = ref(null)
    const qrVideo = ref(null)
    const proyectos = ref([])

    const confirmarEntrada = async () => {
      try {
        const response = await axios.post('/asistencias/checkin', {
          proyecto: proyecto.value,
          codigo: codigoManual.value
        })
        alert('Check-in registrado: ' + response.data.horaEntrada)
      } catch (error) {
        alert('Error: ' + error.response.data.message)
      }
    }

    return {
      proyecto,
      codigoManual,
      ultimaAsistencia,
      qrVideo,
      proyectos,
      confirmarEntrada,
      abrirCamara: () => { /* QR.js logic */ }
    }
  }
}
</script>
```

---

## 🧪 TESTING EN LARAVEL

### Test de Nómina (tests/Feature/NominaTest.php)

```php
<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Asistencia;

class NominaTest extends TestCase
{
    public function test_calcula_nomina_correctamente()
    {
        // Setup: Crear técnico con 20 días asistidos
        $usuario = Usuario::factory()->create(['salario_diario' => 300]);
        
        for ($i = 1; $i <= 20; $i++) {
            Asistencia::create([
                'usuario_id' => $usuario->id,
                'fecha' => now()->subDays(22-$i),
                'hora_entrada' => '09:00:00',
                'hora_salida' => '17:00:00',
                'estado' => 'presente'
            ]);
        }

        // Act: Calcular nómina
        $response = $this->post('/nominas/calcular');

        // Assert: Verificar fórmula
        // 20 días × $300 = $6,000
        $this->assertEquals(6000, $usuario->nomina->total_pago);
    }

    public function test_descuentos_por_faltas()
    {
        $usuario = Usuario::factory()->create(['salario_diario' => 300]);
        
        // 20 presentes
        for ($i = 1; $i <= 20; $i++) {
            Asistencia::create([...]);
        }
        
        // 2 ausentes
        Asistencia::create(['usuario_id' => $usuario->id, 'estado' => 'ausente']);
        Asistencia::create(['usuario_id' => $usuario->id, 'estado' => 'ausente']);

        $this->post('/nominas/calcular');

        // 20 × $300 - (2 × $300) = $5,400
        $this->assertEquals(5400, $usuario->nomina->total_pago);
    }
}
```

---

## 📅 CRONOGRAMA SPRINT-BASED (16 semanas)

### Sprint 1-2 (Semanas 1-2): Setup + DB
- [ ] Crear proyecto Laravel
- [ ] Configurar base de datos en Hostinger
- [ ] Crear migraciones
- [ ] Setup Vue.js + Tailwind CSS
- [ ] Autenticación básica

### Sprint 3-4 (Semanas 3-4): Página Web
- [ ] Homepage + servicios
- [ ] Portfolio
- [ ] Formulario contacto
- [ ] Responsive
- [ ] Deploy a Hostinger

### Sprint 5-6 (Semanas 5-6): Asistencias
- [ ] Controller de asistencias
- [ ] Componente Vue Checkin
- [ ] Tabla de asistencias (RH)
- [ ] Reportes

### Sprint 7-8 (Semanas 7-8): Nómina ⭐
- [ ] Model y Service de nómina
- [ ] Job automático (Laravel Scheduler)
- [ ] Componente Vue MiNomina
- [ ] Reportes para contador
- [ ] Email automático

### Sprint 9-10 (Semanas 9-10): Documentos
- [ ] Model y Controller documentos
- [ ] Upload/Download
- [ ] Árbol de carpetas (Vue)
- [ ] Permisos

### Sprint 11-12 (Semanas 11-12): Dashboard + Integración
- [ ] Dashboard Controller
- [ ] Componentes Vue Dashboard
- [ ] Gráficos Chart.js
- [ ] Testing integración

### Sprint 13 (Semana 13): QA
- [ ] Tests unitarios
- [ ] Tests Feature
- [ ] Tests seguridad
- [ ] Bug fixes

### Sprint 14-16 (Semanas 14-16): Capacitación + Go-Live
- [ ] Capacitar personal CSM
- [ ] Migración de datos
- [ ] Monitoreo
- [ ] Ajustes finales

---

## 🔗 INSTRUCCIONES PARA CLAUDE CODE

Cuando uses Claude Code, proporciona este contexto:

```
Proyecto: CSM Operaciones
Stack: Laravel 11 + Vue.js + Blade + MySQL

TAREAS ACTUALES:
1. Crear Controller AsistenciaController
   - Método checkin: POST /asistencias/checkin
   - Recibe: proyecto, codigo_qr, foto (base64), ubicacion
   - Valida usuario, crea registro
   - Retorna JSON con hora_entrada

2. Crear Componente Vue: Checkin.vue
   - Selector de proyecto
   - Botón para cámara QR (jsQR library)
   - Input manual de código
   - Muestra última entrada/salida

3. Crear Test NominaTest.php
   - Test: 20 días × $300 = $6,000
   - Test: con 2 faltas = $5,400
   - Test: con 2 retardos = $5,700

ESPECIFICACIONES:
- Contraseñas bcrypt (automático)
- Nóminas encriptadas AES-256
- Auditar cada acción (Trait Auditable)
- Permisos por rol (Gate/Policy)

Base de datos: Ver schema anterior
Rutas: Ver rutas Laravel anterior
```

---

## ⚡ TIPS IMPORTANTES

1. **Nómina es crítico:** Cualquier error afecta sueldos. Test exhaustivamente.
2. **Laravel Scheduler:** Cálculo automático cada noche a las 23:00 (crontab)
3. **Encriptación AES-256:** Crear Trait Encryptable que automáticamente encripta/desencripta en modelos
4. **Vue con Blade:** Usar `@vite` en Blade templates para cargar componentes Vue
5. **Hostinger tiene limitaciones:** No tiene SSH completo. Usar Git para deploy y cPanel para configuración
6. **Archivos:** Guardar en `storage/app/documentos/` (privado), no public/
7. **Logs:** Ver en `storage/logs/laravel.log` para debugging

---

## 📞 CONTACTOS

- **Director CSM:** Juan Héctor Morín Torres
- **Desarrollador:** José Ricardo Becerra (DevSistems)
- **Hosting:** Hostinger cPanel
- **Dominio:** csm-operaciones.com.mx

---

**¡Listo para empezar con Laravel! 🚀**


Admin:


Correo:      director@csm-operaciones.com.mx
Contraseña:  password
RH:


Correo:      rh@csm-operaciones.com.mx
Contraseña:  password
