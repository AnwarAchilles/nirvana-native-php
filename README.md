
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
| `baseurl` | `string` | **Required** set default baseurl from the project |


#### Nirvana inner Rest

| Method & Properties | Type  | Description |
| :-------- | :------- | :------------------------- |
| `method` | `string` | return data on method requested. |
| `load` | `string` | load another Rest. |
| `data` | `string` | **(Under Development)** |


#### Nirvana outside Rest

| Method & Properties | Type  | Description |
| :-------- | :------- | :------------------------- |
| `ifNotFound` |  | set default 404 not found if request not have Rest. |

#
[![portfolio](https://ik.imagekit.io/anwarachilles/devneet-powered.svg?updatedAt=1704715329026)]('#')