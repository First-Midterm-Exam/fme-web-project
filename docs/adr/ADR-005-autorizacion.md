# ADR-005 · Autorización por rol

**Fecha:** 2026-09-20 · **Estado:** Aceptado · **Historia:** HU-03 · Restricción por rol

## Contexto

La plataforma tiene cuatro roles (Administrador, Gestor de Procesos, Jefe de Proyecto, Colaborador) y cada usuario tiene exactamente uno. HU-01 y HU-02 dejaron la autenticación y el CRUD de usuarios funcionando sobre `spatie/laravel-permission`, con los roles persistidos en la tabla `roles` y asignados por el pivote `model_has_roles`. HU-03 debe garantizar que ninguna acción quede accesible para un rol que no corresponde, y que ocultar un botón no sea la única defensa (RNF-04).

## Decisión

Se construye la capa de autorización con **Policies y Gates nativos de Laravel**, apoyada en la estructura de roles que ya existe. El rol sigue siendo una **entidad persistida** (`App\Models\Rol`, que extiende el modelo de Spatie y se registra en `config/permission.php`), con constantes de clase para sus identificadores, de modo que ningún punto del código compare roles por cadena suelta. El mapa capacidad → roles vive en un único archivo (`App\Support\Capacidades`) y de ahí se derivan todos los Gates.

## Alternativas descartadas

- **Enum de roles (`App\Enums\Rol`)**: el diagrama de clases define Rol como entidad con identidad y fecha de creación, y un enum obliga a redesplegar para agregar un rol.
- **Reemplazar `spatie/laravel-permission` por una tabla propia con FK `users.rol_id`**: era la opción más fiel al diagrama, pero HU-01 y HU-02 ya están entregadas y funcionando sobre el paquete; migrar el pivote a una FK habría reescrito el CRUD de usuarios, el registro y sus pruebas sin ganancia funcional. Se conserva el paquete como **almacén de roles** y se prohíbe usar su capa de permisos (`Permission`, `syncPermissions`, `@hasrole`): la autorización se decide siempre con Gates y Policies.
- **Comparar `$user->hasRole('Administrador')` en cada controlador**: disperso, frágil ante un renombre y sin cobertura garantizada.

## Consecuencias

- Las rutas críticas se protegen con `can:` (Gate/Policy) o con el alias `rol:`; el ocultamiento en la interfaz es un complemento, nunca la defensa.
- Agregar un rol nuevo es insertar una fila y una constante; agregar una capacidad es una línea en `Capacidades::MAPA`.
- `fechaCreacion` del diagrama de clases se materializa en `roles.created_at`; no se agregó una columna `fecha_creacion` duplicada.
- `Rol::usuarios()` es una relación `MorphToMany` (por el pivote de Spatie), no `HasMany`.
- La integridad referencial del rol la garantiza la FK de `model_has_roles` hacia `roles`.

## Cómo agregar autorización a un módulo nuevo

1. Declarar la capacidad como constante en `App\Support\Capacidades` y agregarla a `Capacidades::MAPA` con los ids de rol que la pueden ejercer (usando las constantes de `App\Models\Rol`). El Gate queda registrado solo con eso.
2. Registrar el módulo en `App\Support\Modulos::todos()` indicando `capacidad`, `ruta`, `icono` e `historia`. El menú, el panel principal y la ruta placeholder se construyen desde ahí.
3. Proteger la ruta con `->middleware(['auth', 'can:<capacidad>'])`, o con `->middleware(['auth', 'rol:<slug>'])` cuando la restricción sea por rol puro.
4. Si la decisión depende del registro concreto y no solo del rol, crear una Policy del modelo y llamar `$this->authorize()` en el componente Livewire, además del `can:` de la ruta.
5. Cubrirlo en `tests/Feature/AutorizacionTest.php`: un caso 200 por rol autorizado y un 403 por URL directa para cada rol no autorizado.

## Pendiente para HU-04: alcance por proyecto asignado (RN-07)

RN-07 exige que un Jefe de Proyecto solo vea y edite evidencias de los proyectos que tiene asignados. Hoy no existen el modelo `Proyecto` ni la tabla intermedia usuario–proyecto, así que la restricción implementada en HU-03 llega solo hasta el nivel de rol: el Jefe de Proyecto tiene la capacidad `registrar-evidencia`, pero no hay con qué acotarla a sus proyectos.

Cuando HU-04 entregue la asignación usuario–proyecto:

- Agregar `User::proyectos()` y una `EvidenciaPolicy` que combine el Gate de rol con la pertenencia al proyecto de la evidencia.
- Aplicar un scope de consulta para que los listados no devuelvan evidencias de proyectos ajenos, no solo que el detalle responda 403.
- Reemplazar la prueba marcada con `->todo()` en `tests/Feature/AutorizacionTest.php` ("un jefe de proyecto solo ve evidencias de sus proyectos asignados") por su implementación real.
