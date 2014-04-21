# OracleDb

Пакет OrackleDb предоставляет собой легковесную обертку вокруг расширения oci8 для php.
Основные возможности

## Введение

### Установка

Эта библиотека требует PHP 5.5 или более позднюю версию.
Установка возможна через composer из custom repository.

## Начинаем работать

Создать новый инстанс БД можно следующим образом

```php
<?php
use nightlinus\OracleDb\Db;

$db = new Db("USER", "password", "DEV");
```

При этом соединение с базой происходит только при первом обращении к ней.
Соединение может быть как общее для всех инстансов класса,
так и отдельным для каждого инстанса, это контролируется конфигурационной переменной `connection.cache`

```php

$db->config('connection.cache', true);
```
В таком виде текущий инстанс будет использовать уже имеющееся соединение с базой данных

```php

$db->config('connection.cache', false);
```
В таком — будет использовать всегда новое соединение с базой данных. Данное воведение выставлено по умолчанию.

Список текущих настроек и их значения по умолчанию:
* session.charset         => AL32UTF8
* session.autocommit      => false
* session.dateFormat      => DD.MM.YYYY HH24:MI:SS
* session.dateLanguage    => false
* connection.persistent   => false
* connection.privileged   => OCI_DEFAULT
* connection.cache        => false
* connection.class        => false
* connection.edition      => false
* client.identifier       => 
* client.info             => 
* client.moduleName       => 
* profiler.enabled        => false
* profiler.class          => __NAMESPACE__ . \\Profiler
* statement.cache.enabled => true
* statement.cache.size    => 50
* statement.cache.class   => __NAMESPACE__ . \\StatementCache

### Создание Statement'a

В библиотеке представлены 2 способа  инстанциировать `Statement`:

```php
$sql = 'SELECT * FROM DUAL';
$statement = $db->prepare($sql);
```

или

```php

$sql = 'SELECT * FROM DUAL';
$statement = $db->query($sql);
```

Во втором случае `Statement` будет сразу выполнен (будет неявно вызван метод `execute`).