# FunSQL

Un **Query Builder para PHP y MySQL** extremadamente liviano, orientado a construir consultas SQL utilizando arrays de PHP sin depender de frameworks externos.

Su objetivo es simplificar la escritura de consultas complejas manteniendo el control absoluto sobre el SQL generado.

---

## Características

- ✔ SELECT
- ✔ INSERT
- ✔ UPDATE
- ✔ DELETE
- ✔ INNER JOIN
- ✔ LEFT JOIN
- ✔ GROUP BY
- ✔ ORDER BY
- ✔ LIMIT
- ✔ Actualizaciones múltiples (`CASE WHEN`)
- ✔ Obtener el SQL generado sin ejecutarlo
- ✔ Inspección automática de la estructura de la base de datos
- ✔ Compatible con MySQL mediante `mysqli`

---

# Instalación

Simplemente incluya el archivo.

```php
require_once "fun_sql.php";
```

Crear la conexión:

```php
$db = new db(
    "localhost",
    "usuario",
    "contraseña",
    "basededatos"
);

$db->conectar();
```

---

# Filosofía

En lugar de escribir consultas SQL manualmente:

```sql
SELECT *
FROM usuarios
WHERE id = 5
```

FunSQL permite construirlas mediante estructuras PHP:

```php
$db->select(
    false,
    "usuarios",
    "*",
    ["id","=",5]
);
```

Esto hace que las consultas sean más fáciles de generar dinámicamente.

---

# SELECT

## Consulta simple

```php
$resultado = $db->select(
    false,
    "usuarios",
    "*"
);
```

---

## Seleccionar campos específicos

```php
$resultado = $db->select(
    false,
    "usuarios",
    [
        "id",
        "nombre",
        "apellido"
    ]
);
```

---

## WHERE

```php
$resultado = $db->select(
    false,
    "usuarios",
    "*",
    ["id","=",15]
);
```

---

## Múltiples condiciones

```php
[
    ["activo","=",1],
    ["edad",">",18],
    ["pais","=","Argentina","OR"]
]
```

Genera:

```sql
WHERE activo = 1
AND edad > 18
OR pais = 'Argentina'
```

---

## ORDER BY

```php
[
    "nombre",
    true
]
```

Genera:

```sql
ORDER BY nombre ASC
```

```php
[
    "nombre",
    false
]
```

Genera:

```sql
ORDER BY nombre DESC
```

---

## LIMIT

```php
$db->select(
    false,
    "usuarios",
    "*",
    null,
    null,
    null,
    20
);
```

---

# JOIN

Puede realizar INNER JOIN o LEFT JOIN utilizando arrays.

Ejemplo:

```php
$db->select_nw(false,[
    "tables"=>"usuarios",

    "join"=>[
        [
            "roles",
            ["usuarios.rol","=","roles.id"]
        ]
    ],

    "field"=>[
        "usuarios.nombre",
        "roles.descripcion"
    ]
]);
```

También admite múltiples JOIN.

---

# INSERT

```php
$db->create(
    false,
    "usuarios",
    [
        "nombre"=>"Juan",
        "edad"=>25
    ]
);
```

Devuelve automáticamente el último ID insertado.

---

# UPDATE

```php
$db->update(
    false,
    "usuarios",
    [
        "nombre"=>"Pedro"
    ],
    [
        "id","=",10
    ]
);
```

---

# Actualización múltiple

La librería permite generar automáticamente consultas del tipo:

```sql
UPDATE tabla
SET estado =
CASE id
WHEN 1 THEN 2
WHEN 2 THEN 3
WHEN 3 THEN 5
END
```

Utilizando:

```php
$db->multiUpdate(...)
```

Ideal para cambios masivos.

---

# DELETE

```php
$db->remove(
    false,
    "usuarios",
    [
        "id","=",20
    ]
);
```

---

# Obtener únicamente el SQL

Todos los métodos poseen como primer parámetro:

```php
$retQ
```

Cuando vale `true`, **no ejecuta** la consulta sino que devuelve el SQL generado.

Ejemplo:

```php
$sql = $db->select(
    true,
    "usuarios",
    "*",
    ["id","=",5]
);
```

Resultado:

```sql
SELECT *
FROM usuarios
WHERE id = 5
```

Esto resulta muy útil para depuración.

---

# Métodos modernos

La librería incorpora una segunda generación de métodos:

- `select_nw()`
- `nw_update()`
- `remove_nw()`

Estos utilizan un único array asociativo para definir todos los parámetros de la consulta, facilitando la construcción dinámica de SQL.

Ejemplo:

```php
$db->select_nw(false,[

    "tables"=>"usuarios",

    "field"=>[
        "id",
        "nombre"
    ],

    "conditions"=>[
        "activo","=",1
    ],

    "order"=>[
        "nombre",
        true
    ],

    "limit"=>20

]);
```

---

# Información de la Base de Datos

La función:

```php
$db->getDataBaseFields();
```

consulta automáticamente `INFORMATION_SCHEMA` y devuelve:

- tablas
- campos
- tipos
- claves primarias
- claves foráneas
- relaciones

Ideal para construir aplicaciones dinámicas o generadores de formularios.

---

# Compatibilidad

- PHP 7+
- PHP 8+
- MySQL
- MariaDB

---

# Dependencias

Únicamente utiliza:

- mysqli

No requiere Composer.

---

# Licencia

MIT