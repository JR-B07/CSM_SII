# CSM OPERACIONES - ESPECIFICACIÓN TÉCNICA

> **Estado: en producción.** Este documento empezó como la especificación de diseño antes de desarrollar el sistema. Ahora describe el sistema **tal como está construido y desplegado**, incluyendo módulos que no estaban en el plan original y ajustes que surgieron durante el desarrollo y la operación real. La sección "Historial de cambios" resume qué cambió respecto al plan inicial.

## 📋 RESUMEN DEL PROYECTO

**Empresa:** CSM Operaciones (Servicios Técnicos Industriales)
**Ubicación:** San Luis de la Paz, Guanajuato, México
**Empleados:** 14
**Director:** Juan Héctor Morín Torres
**Desarrollador:** José Ricardo Becerra Covarrubias (DevSistems)

**Sitio en producción:** `https://csm-operaciones-sii.devsistems.com` (Hostinger, plan compartido)
**Repositorio:** GitHub (`JR-B07/CSM_SII`, rama `main`), desplegado por Hostinger vía Git a `public_html/csm-app`

**Objetivo:** Plataforma integral con Laravel + Vue.js: página web pública, portal interno de empleados con asistencias por check-in, nómina quincenal automática, gestión de documentos de proyecto y documentos legales del personal, y una librería de formatos imprimibles.

---

## 🔄 HISTORIAL DE CAMBIOS RESPECTO AL PLAN ORIGINAL

Lo más importante para quien retome este proyecto:

- **Nómina pasó de mensual a quincenal** (corte 1-15 y 16-fin de mes), con recálculo **en vivo** en cada check-in/check-out/justificación — no depende solo del job nocturno.
- **Se quitó el descuento automático por retardos.** Un día "tarde" se paga igual que "presente"; el estado queda registrado para referencia de RH, pero ya no resta del pago. Solo las faltas (`ausente`) generan descuento.
- **RH ahora marca manualmente una nómina como "pagada"** (antes no existía esa acción en la UI).
- **El rol `técnico` se renombró a `rescatista`** en todo el sistema (base de datos incluida).
- **Se agregaron 3 módulos que no estaban en el plan original:**
  - **Formatos** — librería de checklists/formatos de trabajo/triggers, subibles e imprimibles por cualquier empleado.
  - **Documentos legales** — papelería personal de cada empleado (IMSS, INE, RFC, CURP, contrato, etc.), gestionada por RH/admin.
  - **Registro y aprobación de cuentas** — autorregistro de empleados con cola de aprobación por un admin (antes todas las cuentas las creaba un admin directamente).
- **Gestión de usuarios (CRUD + dar de baja/reactivar)** se agregó completa; no estaba detallada en el plan original.
- **No se usa Laravel Passport, Vue Router ni un SPA real.** Cada vista es una página Blade normal que monta **un** componente Vue aislado vía `createApp().mount('#selector')`. Vue Router está en `package.json` pero no se usa.
- **El Laravel Scheduler no corre como daemon** (`schedule:work`): Hostinger no permite procesos persistentes. Se simula con un cron cada ~2 minutos que ejecuta `php artisan schedule:run`.
- **No hay worker de colas corriendo en producción** (`QUEUE_CONNECTION=database` pero nada consume la tabla `jobs`). El job nocturno de nómina (`CalcularNominasJob`) sigue existiendo como respaldo, pero el cálculo real ya no depende de él — ver nota en Nómina.
- **Hostinger shared hosting impuso varias limitaciones no previstas** que cambiaron cómo se despliega todo — ver la sección "Despliegue en Hostinger".

---

## 🎯 MÓDULOS

### 1. PÁGINA WEB PÚBLICA
- Homepage, Servicios, Portfolio, Blog, Formulario de contacto.
- `HomepageController`, `ServiciosController`, `PortfolioController`, `BlogController`, `ContactoController` + vistas en `resources/views/web/`.
- Blade + Tailwind CSS, sin Vue (no necesita interactividad).

### 2. AUTENTICACIÓN Y CUENTAS
- Login con "Recordarme" (cookie persistente + email recordado prellenado en el formulario).
- **Autorregistro** (`/registro`): un visitante crea su cuenta, nace con `estado_cuenta = pendiente` y **no puede iniciar sesión** hasta que un admin la apruebe. El registro ignora cualquier rol enviado por el usuario (siempre se asigna después, al aprobar).
- **Cola de aprobación** (`/usuarios/pendientes`, solo RH/admin): aprobar define rol, departamento y salario diario (datos que el autorregistro nunca captura); rechazar admite un motivo opcional. Ambas acciones envían correo.
- `AuthController`, `RegistroController`.

### 3. GESTIÓN DE USUARIOS (solo admin)
- CRUD completo (`UsuarioController`), dar de baja / reactivar (no se borra físicamente: desactivar preserva historial de asistencias/nómina), reset de contraseña.
- Listado con búsqueda en cliente y `paginate(200)` (sin UI de paginación — 200 cubre de sobra el tamaño real de la empresa; ver nota de bug corregido más abajo).

### 4. SISTEMA DE ASISTENCIAS
- Check-in/check-out con captura de ubicación GPS y **hora del dispositivo del empleado** (no la del servidor — si no llega, usa la del servidor como respaldo).
- Campos libres por asistencia: **proyecto, compañía, puesto** (capturados en cada check-in, no vinculados a un catálogo fijo).
- Escaneo de QR (librería `jsqr`) o código manual.
- `estado` se marca `tarde` automáticamente si la hora de entrada pasa de `config('custom.asistencia.hora_limite')` (default 09:15:00); ya no genera descuento en nómina, solo queda registrado.
- Vista de administración (RH/admin) con filtro por fecha y botón de justificar falta.
- **No implementado del plan original:** captura de foto de verificación — el modelo/controlador tienen el soporte (`guardarFoto()`), pero `Checkin.vue` no la envía actualmente.

### 5. DOCUMENTOS DE PROYECTO
- Upload/descarga (máx. 50MB, sin restricción de tipo de archivo), organizados por cliente/proyecto, versionado automático, permisos granulares por documento (`permisos_documentos`), búsqueda en la lista.
- `DocumentoController`, `DocumentoService`.

### 6. DOCUMENTOS LEGALES (nuevo, no estaba en el plan original)
- Papelería personal de cada empleado: IMSS, INE, RFC, CURP, comprobante de domicilio, contrato, acta de nacimiento, otro — con fecha de vencimiento opcional (para IMSS/contrato).
- Gestionados por RH/admin desde el perfil de cada usuario (`/usuarios/{id}/documentos-legales`); el propio empleado solo puede ver (no subir) los suyos.
- Retención legal: `softDeletes`, nunca se borra físicamente.
- `DocumentoLegalController`.

### 7. FORMATOS (nuevo, no estaba en el plan original)
- Librería compartida de checklists, formatos de trabajo y triggers — cualquier empleado autenticado puede subir, consultar, filtrar por tipo e imprimir.
- Archivos permitidos: **PDF, JPG, PNG, Word (doc/docx), Excel (xls/xlsx)**, máx. 10MB. PDF e imagen se ven e imprimen directo desde el navegador ("Ver / Imprimir"); Word/Excel se descargan (los navegadores no los renderizan inline).
- `FormatoController`.

### 8. NÓMINA QUINCENAL AUTOMÁTICA ⭐ CRÍTICO
- **Periodo:** quincenal, corte **1-15** y **16-fin de mes** (no mensual como en el plan original).
- **Fórmula:** `(Días asistidos × Salario diario) − Descuento por faltas = Total a pagar`
  - Faltas (`ausente`): −100% del día.
  - Retardos (`tarde`) y permisos justificados (`justificado`): **sin descuento**, se pagan como día asistido.
- **Cálculo en vivo:** cada check-in, check-out y justificación de falta recalcula inmediatamente la nómina del periodo en curso (`NominaService::calcularNomina()`), para que "Mi Nómina" nunca dependa de esperar al job nocturno.
- `CalcularNominasJob` sigue corriendo cada noche a las 23:00 (vía cron → `schedule:run`) como respaldo/corrección, no como mecanismo principal.
- RH puede **marcar una nómina como "pagada"** (`POST /nominas/{id}/pagar`); el recálculo en vivo nunca pisa ese estado una vez marcado.
- Comprobante en PDF por periodo, histórico de últimos periodos, reporte para contador en JSON/CSV.
- **Seguridad:** `total_pago` encriptado AES-256-CBC (Trait `Encryptable`), nunca se borra físicamente (`softDeletes`, retención legal de 7 años).
- `NominaController`, `NominaService`, `CalcularNominasJob`.

### 9. DASHBOARD EJECUTIVO
- KPIs: asistencias hoy, ausencias hoy, documentos totales, proyectos activos hoy.
- Gráficos: asistencia por día (últimos 7 días), asistencia por departamento (hoy) — Chart.js vía `vue-chartjs`.
- `DashboardController`.

---

## 🏗️ ARQUITECTURA TÉCNICA

### Stack real

**Backend:**
- Laravel 11.31+ / PHP 8.2+ (producción corre PHP 8.4)
- `barryvdh/laravel-dompdf` (comprobantes de nómina en PDF)
- Laravel Scheduler — **disparado por cron cada ~2 min**, no por `schedule:work` (no hay procesos persistentes en Hostinger compartido)

**Frontend — patrón híbrido, no SPA:**
- Vue 3 (Options API, `setup()`), montado componente-por-componente con `createApp(Componente).mount('#selector')` sobre vistas Blade normales (ver `resources/js/app.js`)
- **No se usa Vue Router** aunque está instalado
- Tailwind CSS 3 con clases semánticas propias (`page-header`, `card`, `btn-primary`, `table-base`, `badge-green`, etc. — ver `resources/css/app.css`)
- Chart.js + `vue-chartjs` (Dashboard)
- `jsqr` (escaneo de QR en check-in)
- Vite + `laravel-vite-plugin` — **los assets compilados se commitean a git** (`public/build/`), porque Hostinger no tiene Node/npm

**Base de datos:**
- MySQL (Hostinger). Local: MySQL también (no SQLite) para producción-paridad; tests usan SQLite en memoria vía `RefreshDatabase`.

**Seguridad:**
- Bcrypt para contraseñas (cast `hashed` nativo de Laravel)
- AES-256-CBC para nóminas (Trait `Encryptable` propio — no es Laravel Passport/Sanctum, es sesión + CSRF estándar)
- CSRF estándar de Laravel
- Auditoría propia (Trait `Auditable`, tabla `auditoria`) — no hay 2FA implementado

**No implementado del plan original:** Laravel Passport/OAuth, 2FA, Mailtrap/SendGrid en producción (`MAIL_MAILER=log`, pendiente de decidir si se configura SMTP real).

---

## 📊 BASE DE DATOS — SCHEMA ACTUAL

### `usuarios`
```sql
id, nombre, email (unique),
rol ENUM('rescatista','supervisor','RH','admin','contador') default 'rescatista',
departamento, salario_diario DECIMAL(10,2),
password (bcrypt), activo BOOLEAN default true,
estado_cuenta ENUM('pendiente','aprobado','rechazado') default 'aprobado',
aprobado_por (FK → usuarios, nullable), aprobado_en (timestamp, nullable),
remember_token, timestamps
```

### `asistencias`
```sql
id, usuario_id (FK), fecha (DATE),
hora_entrada, hora_salida (TIME, nullable),
ubicacion_lat DECIMAL(10,8), ubicacion_lng DECIMAL(11,8),
foto_verificacion (nullable, no usado actualmente),
proyecto, compania, puesto (VARCHAR, capturados libremente en cada check-in),
estado ENUM('presente','ausente','tarde','justificado'),
justificante (TEXT, nullable), timestamps
UNIQUE(usuario_id, fecha)
```
> `ubicacion_lng` es `DECIMAL(11,8)`, no `(10,8)` como en el plan original: con 2 dígitos enteros no alcanza para longitudes de 3 dígitos (México ronda -100.x).

### `documentos`
```sql
id, nombre, ruta_archivo, tipo (string libre), tamano (bytes),
cliente, proyecto, usuario_id (FK), version INT default 1,
timestamps, soft deletes
```

### `permisos_documentos`
```sql
id, documento_id (FK), usuario_id (FK),
nivel_permiso ENUM('lectura','lectura_descarga','edicion','admin'), timestamps
```

### `documentos_legales` (nuevo)
```sql
id, usuario_id (FK), tipo ENUM('IMSS','INE','RFC','CURP','comprobante_domicilio','contrato','acta_nacimiento','otro'),
numero_documento (nullable), nombre_archivo, ruta_archivo,
fecha_vencimiento (DATE, nullable), subido_por (FK → usuarios),
timestamps, soft deletes
```

### `formatos` (nuevo)
```sql
id, nombre, tipo ENUM('checklist','formato_trabajo','trigger'),
nombre_archivo, ruta_archivo, subido_por (FK → usuarios),
timestamps, soft deletes
```

### `nominas`
```sql
id, usuario_id (FK),
periodo_inicio DATE, periodo_fin DATE, tipo_periodo VARCHAR(20) default 'quincenal',
salario_diario DECIMAL(10,2), dias_laborales INT, dias_asistidos INT,
descuentos_faltas DECIMAL(10,2) default 0,
total_pago TEXT (encriptado AES-256-CBC),
estado ENUM('calculada','pagada','procesada') default 'calculada',
fecha_pago (DATE, nullable), timestamps, soft deletes
UNIQUE(usuario_id, periodo_inicio)
```
> Originalmente era `mes`/`anio` (mensual) con columna `descuentos_retardos`. Ambas cosas cambiaron — ver Historial de cambios.

### `auditoria`
```sql
id, usuario_id (FK), accion, tabla_afectada, registro_id,
detalles (JSON), ip_address, created_at
```

---

## 🔌 RUTAS PRINCIPALES (ver `routes/web.php` para el listado completo)

```php
// Público
GET  /                              HomepageController
GET  /servicios | /portfolio | /blog
POST /contacto

// Auth
GET|POST /login
POST     /logout
GET|POST /registro                  → autorregistro, nace "pendiente"

// Autenticado (cualquier rol)
GET  /mi-perfil
GET  /dashboard  (+ /dashboard/kpis, /dashboard/graficos)
GET|POST /asistencias/checkin
POST /asistencias/checkout
GET  /documentos (+ /documentos/data, upload, download, permisos)
GET  /formatos   (+ /formatos/data, upload, {id}/ver, {id} delete)
GET  /nominas/mi-nomina (+ data, pdf, /nominas/historico)
GET  /usuarios/{id}/documentos-legales/data   (solo las propias)

// Solo RH/admin
GET  /asistencias (gestión) + justificar
GET  /nominas (gestión) + calcular + {id}/pagar + reporte-contador(.csv)
GET  /usuarios/pendientes (+ data) + {id}/aprobar + {id}/rechazar
GET|POST /usuarios/{id}/documentos-legales (gestión)

// Solo admin
/usuarios (CRUD completo) + reset-password + {id}/activar
```

---

## 🗂️ ESTRUCTURA DE CARPETAS (real)

```
CSM_SII/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php, RegistroController.php, PerfilController.php
│   │   ├── DashboardController.php
│   │   ├── AsistenciaController.php
│   │   ├── DocumentoController.php, DocumentoLegalController.php
│   │   ├── FormatoController.php
│   │   ├── NominaController.php
│   │   ├── UsuarioController.php
│   │   └── HomepageController.php, ServiciosController.php, PortfolioController.php, BlogController.php, ContactoController.php
│   ├── Http/Requests/
│   │   ├── StoreAsistenciaRequest.php, StoreDocumentoRequest.php
│   │   ├── StoreDocumentoLegalRequest.php, StoreFormatoRequest.php
│   ├── Models/
│   │   ├── Usuario.php, Asistencia.php, Documento.php, DocumentoLegal.php
│   │   ├── Formato.php, PermisoDocumento.php, Nomina.php, Auditoria.php
│   ├── Services/
│   │   ├── NominaService.php, DocumentoService.php, AuditService.php, EmailService.php
│   ├── Jobs/
│   │   └── CalcularNominasJob.php (respaldo nocturno, no mecanismo principal)
│   └── Traits/
│       ├── Encryptable.php, Auditable.php
│
├── resources/
│   ├── views/
│   │   ├── layouts/app.blade.php (header/nav), layouts/guest.blade.php
│   │   ├── auth/login.blade.php, auth/registro.blade.php
│   │   ├── dashboard/index.blade.php
│   │   ├── asistencias/checkin.blade.php, asistencias/index.blade.php
│   │   ├── documentos/index.blade.php
│   │   ├── formatos/index.blade.php
│   │   ├── nominas/mi-nomina.blade.php, nominas/index.blade.php, nominas/comprobante-pdf.blade.php
│   │   ├── usuarios/index.blade.php, usuarios/form.blade.php, usuarios/pendientes.blade.php, usuarios/documentos-legales.blade.php
│   │   ├── perfil/mostrar.blade.php
│   │   └── web/ (homepage, servicios, portfolio, blog)
│   ├── js/
│   │   ├── app.js (monta cada componente por selector, SIN Vue Router)
│   │   └── components/
│   │       ├── Checkin.vue, Dashboard.vue, MiNomina.vue
│   │       ├── AsistenciasAdmin.vue, NominasAdmin.vue
│   │       ├── DocumentosProyecto.vue, DocumentosLegalesAdmin.vue
│   │       ├── Formatos.vue, UsuariosAdmin.vue, UsuariosPendientes.vue
│   └── css/app.css (Tailwind + clases semánticas propias)
│
├── database/
│   ├── migrations/ (16 migraciones — ver schema arriba; varias son correctivas sobre migraciones ya aplicadas, nunca se editan las originales)
│   ├── seeders/UsuarioSeeder.php
│   └── factories/UsuarioFactory.php
│
├── routes/web.php, routes/console.php (Schedule::job(CalcularNominasJob)->dailyAt('23:00'))
├── config/custom.php (hora_limite de asistencia)
├── storage/app/ (documentos, documentos_legales, formatos, asistencias — todo privado, nunca en public/)
└── tests/Feature/ + tests/Unit/ (80 tests a la fecha, PHPUnit vía `php artisan test`)
```

---

## 🚀 DESPLIEGUE EN HOSTINGER (plan compartido — sin SSH)

Esta sección documenta restricciones reales del hosting que obligaron a desviarse del despliegue estándar de Laravel. Es la parte más importante para cualquiera que retome el mantenimiento del sitio.

### Restricciones del plan contratado
- **Sin SSH** — todo se hace vía hPanel (File Manager, Git, Cron Jobs, phpMyAdmin) o por Git push.
- **Sin Node/npm en el servidor** — Vite debe compilarse **localmente** y los archivos de `public/build/` deben **commitearse a git** (por eso NO está en `.gitignore`).
- **`proc_open` deshabilitado** — cualquier script de Composer que use `@php artisan ...` como subproceso falla. `composer.json` solo tiene el callback en-proceso `Illuminate\Foundation\ComposerScripts::postAutoloadDump`; se commitea un `bootstrap/cache/packages.php` pre-generado como respaldo.
- **Sin control del docroot** — el plan Single no permite apuntar el dominio a `public_html/csm-app/public` (la ubicación estándar de Laravel). Solución ("Plan B"):
  - El contenido de `public/` vive duplicado como la raíz real servida en `public_html/`.
  - `public_html/index.php` es una copia de `public/index.php` con sus 3 rutas relativas reescritas para apuntar a `public_html/csm-app/...`.
  - `public_html/.htaccess` tiene las reglas estándar de reescritura de Laravel.
  - `public_html/csm-app/.htaccess` (nuevo, no existe en un Laravel normal) bloquea el acceso directo a esa carpeta con `Require all denied`.
- **Sin worker de colas** — `CalcularNominasJob implements ShouldQueue` se encola pero nada la procesa (no hay `queue:work` corriendo). Por eso el cálculo de nómina se mueve a **recálculo síncrono en vivo** (ver módulo de Nómina) en vez de depender del job. Si se necesita que el job nocturno realmente se dispare sin depender de `sync`, considerar cambiar `QUEUE_CONNECTION=sync` en el `.env` de producción.

### ⚠️ Paso manual recurrente (la causa más común de "se ve en blanco" en producción)

Cada vez que se cambia algo en `resources/js` o `resources/css`:

1. `npm run build` localmente.
2. Commitear `public/build/` (los nombres de archivo cambian por el hash de contenido).
3. Push + "Redistribuir" en el panel de Git de Hostinger — esto actualiza `public_html/csm-app/public/build/`.
4. **Copiar manualmente** `public_html/csm-app/public/build/*` sobre `public_html/build/` — el redistribuir **no** hace esto automáticamente, porque `public_html/build/` (lo que realmente sirve el dominio) es una copia aparte del "Plan B", no un symlink.

Olvidar el paso 4 ya causó una caída real de producción (vistas en blanco, `app-*.js` 404) — **siempre recordarlo** después de cualquier redeploy con cambios de frontend.

### Migraciones en producción (sin SSH)
No hay forma de correr `php artisan migrate` directamente. Se usa un **cron job temporal**:
1. hPanel → Avanzado → Cron Jobs → crear uno con el comando (ruta relativa al home, sin repetir el prefijo que ya pone Hostinger):
   `domains/csm-operaciones-sii.devsistems.com/public_html/csm-app/artisan migrate --force`
2. Esperar a que corra una vez (o "Ejecutar ahora" si el panel lo ofrece).
3. Verificar en phpMyAdmin que el cambio se aplicó.
4. **Borrar el cron job** — es de un solo uso, no debe quedar corriendo periódicamente.

### Scheduler permanente
Cron fijo, cada ~2 minutos:
`domains/csm-operaciones-sii.devsistems.com/public_html/csm-app/artisan schedule:run`
Esto es lo que dispara `CalcularNominasJob` a las 23:00 (`routes/console.php`) y cualquier otra tarea programada futura.

### Checklist de un deploy con cambios de backend + frontend
1. `php artisan test` — todo verde.
2. `npm run build` (si hubo cambios en JS/CSS).
3. Commit (el usuario hace `git commit`/`git push`, no el asistente).
4. "Redistribuir" en Hostinger.
5. Si hay migraciones nuevas: cron temporal de `migrate --force`, verificar, borrar el cron.
6. Si hubo cambios de frontend: re-copiar `public_html/csm-app/public/build/*` → `public_html/build/`.

---

## 🔒 SEGURIDAD

- **Autenticación:** sesiones de Laravel + bcrypt. "Recordarme" con `remember_token` (cookie persistente) + email recordado en cookie separada para prellenar el login.
- **Autorización:** roles `rescatista, supervisor, RH, admin, contador` vía middleware `role:` y helpers del modelo (`esRH()` incluye RH+admin, `esAdmin()` solo admin).
- **Encriptación:** AES-256-CBC solo en `nominas.total_pago` (Trait `Encryptable`, con `attributesToArray()` sobreescrito para no filtrar el texto cifrado por error).
- **CSRF:** estándar de Laravel en todos los formularios/axios.
- **Auditoría:** Trait `Auditable` registra automáticamente create/update/delete relevantes en la tabla `auditoria` (usuario, acción, tabla, registro, IP).
- **Retención legal (LFPDPPP):** `nominas`, `documentos`, `documentos_legales` y `formatos` usan `softDeletes` — nunca se borran físicamente.
- **`.gitignore` de `storage/`** debe preservar los placeholders de Laravel mientras excluye contenido real (documentos subidos, logs con datos reales) — ya se corrigió una vez un `.gitignore` incompleto que habría expuesto esto en git.
- **No implementado:** 2FA, Laravel Passport/OAuth, SMTP real (actualmente `MAIL_MAILER=log`).

---

## 🧪 TESTING

- PHPUnit vía `php artisan test` — **80 tests** a la fecha de este documento, todos en verde.
- `RefreshDatabase` + SQLite en memoria para tests (producción usa MySQL — ver nota abajo sobre `whereDate()`).
- Cobertura por módulo: `tests/Feature/` (AsistenciaTest, NominaTest, UsuarioTest, RegistroTest, RecordarmeTest, FormatoTest, DocumentoTest, DocumentoLegalTest) + `tests/Unit/NominaServiceTest.php`.

**Gotcha recurrente a tener presente:** comparar una columna `date`-cast de Eloquent con `where('col', $valor)` falla en SQLite (donde el valor interno queda como datetime completo) pero "funciona por accidente" en MySQL (columna `DATE` real). Usar siempre `whereDate()` para estas comparaciones — ya causó bugs reales en `Asistencia.fecha` y `Nomina.periodo_inicio`.

---

## ⚡ LECCIONES APRENDIDAS

1. **El paso manual de copiar `public/build` a `public_html/build`** es el riesgo operativo más alto del proyecto — ya causó una caída real. Automatizarlo no es posible sin SSH; solo queda disciplina de checklist.
2. **Nunca editar una migración ya aplicada** — no tiene efecto retroactivo en una base de datos real. Siempre crear una migración nueva, incluso para corregir un error de la anterior (ver `2025_01_04`, `2025_01_07`, `2025_01_08`, `2025_01_09`).
3. **`whereDate()` en vez de `where()` para columnas de fecha** — SQLite (tests) no reproduce el bug que sí aparece en MySQL (producción).
4. **Los casts `date` de Eloquent serializan como datetime ISO completo en JSON por defecto** (`2026-09-01T00:00:00.000000Z`), no como fecha simple — usar `'date:Y-m-d'` si el frontend espera una fecha simple.
5. **SoftDeletes + índice único puede bloquear un re-insert** — el índice único de MySQL no distingue filas borradas-suavemente. Cualquier lógica de "buscar o crear" sobre un modelo con `SoftDeletes` debe considerar `withTrashed()` y restaurar si hace falta.
6. **Condiciones de carrera en "buscar o crear"** (p. ej. dos peticiones casi simultáneas) deben manejarse capturando `UniqueConstraintViolationException` y reintentando como actualización, no asumiendo que el `where()->first()` previo es suficiente.
7. **`decimal(N, 8)` para longitud GPS necesita al menos 3 dígitos enteros** (`DECIMAL(11,8)`), porque la longitud llega a ±180; latitud sí cabe en 2 dígitos (`DECIMAL(10,8)`).
8. **Composer con `proc_open` deshabilitado** rompe cualquier script `@php artisan ...` en `composer.json` — usar solo callbacks en-proceso y commitear un `bootstrap/cache/packages.php` de respaldo.
9. **Un límite de paginación bajo sin UI de "siguiente página"** oculta usuarios/registros sin avisar — ya pasó con `Usuario::paginate(20)` en el listado de admin.
10. **Git: el asistente de IA solo hace `git add`**, nunca `commit`/`push` — el usuario siempre ejecuta el commit/push él mismo.

---

## 📞 CONTACTOS

- **Director CSM:** Juan Héctor Morín Torres
- **Desarrollador:** José Ricardo Becerra Covarrubias (DevSistems)
- **Hosting:** Hostinger (plan Single, hPanel)
- **Dominio de producción:** `csm-operaciones-sii.devsistems.com`

> Las credenciales de prueba (seeder) y cualquier contraseña real **no se documentan en este archivo** porque vive en el repositorio de git. Ver `database/seeders/UsuarioSeeder.php` para las cuentas de prueba usadas en desarrollo local.


Admin: director@csm-operaciones.com.mx / password
RH: rh@csm-operaciones.com.mx / password
