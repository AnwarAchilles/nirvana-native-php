
# Nirvana PHP
Lightweight tool for building simple Rest HTTP/API in PHP programming language environment

version - Beta



## Example Use


#### Install with composer
```bash
composer require anwarachilles/nirvana-native-php
```

#### PHP structure
Create an index.php file, then setup environment & rest, like the example below.
```php
Nirvana::environment([
  'Configure'=> [
    'development'=> true,
    'baseurl'=> 'http://localhost/<yourproject>/',
  ]
]);


Nirvana::rest('GET', 'demo', function() {
  return [
    'name'=> Nirvana::method('name'),
    'code'=> Nirvana::method('code'),
  ];
});
```

```php
```


## API Reference


#### Environment configure

| Properties | Type  | Description |
| :-------- | :------- | :------------------------- |
| `development` | `string` | **Optional**. will return response with development dataset |
| `basedir` | `__DIR__` | **Required** set default baseurl from the project |
| `baseurl` | `string` | **Required** set default baseurl from the project |


#### Nirvana inside Rest

| Method & Properties | Type  | Description |
| :-------- | :------- | :------------------------- |
| `method` | `string` | return data on method requested. |
| `load` | `string` | load another Rest. |
| `data` | `string` | **(Under Development)** |
| `store` | `name` | get data from store can do CRUD |


#### Nirvana outside Rest

| Method & Properties | Type  | Description |
| :-------- | :------- | :------------------------- |
| `ifNotFound` | `void` | set default 404 not found if request not have Rest. |
| `store` | `name`, `data array`  | set data to store. |


#### Special handler store

| Method & Properties | Type  | Description |
| :-------- | :------- | :------------------------- |
| `set` | `array` | insert/create data to store |
| `get` | `id`or`void` | view/load data from store |
| `put` | `id`, `array` | update/load data from store |
| `del` | `id` | delete/remove data from store |
| `find` | `field`, `value`or`void` | view data from store with specified field and value, or only field |

#
[![DEVNEET-ID](https://ik.imagekit.io/anwarachilles/devneet-powered.svg?updatedAt=1704715329026)](https://github.com/devneet-id)