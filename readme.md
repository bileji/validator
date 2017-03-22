## Installation

The suggested installation method is via [composer](https://getcomposer.org/):

```sh
php composer.phar require "bileji/validator:~1.0.0"
```

## Usage

### Example

```php
$validator = new \Bileji\Validator\Validator();

$validator->execute([
    "fruit" => "apple",
    "attribute" => [
        [
            "price" => 5,
            "time" => "2016-03-14 12:00:00"
        ],
        [
            "price" => 4,
            "time" => "2016-03-15 12:00:00"
        ]
    ],
    "email" => "shuc324@gmail.com",
    "colors" => ["color1" => "red", "color2" => "green"]
], [
    "fruit" => "required|string",
    "attribute._.price" => "required|numeric",
    "attribute._.time" => "required|string",
    "email" => "email",
    "colors" => "required|map"
]);

if ($validator->fails()) {
    $validator->errors()->first()->getMessage();
} else {
    var_dump($validator->getData());
}

array(4) {
  ["fruit"]=>
  string(5) "apple"
  ["attribute"]=>
  array(2) {
    [0]=>
    array(2) {
      ["price"]=>
      string(1) "5"
      ["time"]=>
      string(19) "2016-03-14 12:00:00"
    }
    [1]=>
    array(2) {
      ["price"]=>
      string(1) "4"
      ["time"]=>
      string(19) "2016-03-15 12:00:00"
    }
  }
  ["email"]=>
  string(17) "shuc324@gmail.com"
  ["colors"]=>
  array(2) {
    ["color1"]=>
    string(3) "red"
    ["color2"]=>
    string(5) "green"
  }
}
```

## Validator

    map
    
    list
    
    string
    
    numeric
    
    float
    
    required
    
    between:min,max
    
    url
    
    email
    
    phone
    
    chinese
    
    regex
    
    before:time
    
    after:time
    
    enum:enum
    
## Custom error message

```php
$validator->withMessage([
    'fruit|required' => ":field的名字不能为空"
])->execute([
    "fruit" => "apple",
    "attribute" => [
        [
            "price" => 5,
            "time" => "2016-03-14 12:00:00"
        ],
        [
            "price" => 4,
            "time" => "2016-03-15 12:00:00"
        ]
    ],
    "email" => "shuc324@gmail.com",
    "colors" => ["color1" => "red", "color2" => "green"]
], [
    "fruit" => "required|string",
    "attribute._.price" => "required|numeric",
    "attribute._.time" => "required|string",
    "email" => "email",
    "colors" => "required|map"
]);
```
