# php-tsuruya


## Description

Words filter and bad words substitutions.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [License](#license)

## Installation

Just PHP and iconv.

## Usage 

### A Tsuruya\Letter will be represented like this:

| Property | Description |
| --- | --- | --- |
| **char** | `string` |  |
| **translit** | `string` |  |
| **locale** | `string` |  |
| **bytes** | `int` |  |
| **bytesTranslit** | `int` |  |
| **index** | `int` |  |
| **pos** |`int`  |  |
| **posTranslit** | `int` |  |

### A Tsuruya\Phrase object will be represented like this:

| Property | Type | Description |
| --- | --- | --- |
| **locale** | `string` | 
| **phrase** | `string` |  |
| **phraseT** | `string` |  |
| **phraseF** | `string` |  |
| **letters** | `array` |  |
| **words** | `array` |  |
| **replaces** | `\stdClass` | 's account |

### Examples

```php
$example = 'Ho can3 ûn g#4*Tt(_______) IN Cå$...aα Chë lîTiga sémpr€ CØL MIÕ Ç/-\n......€! AΑα';
$obj = new \Tsuruya\Phrase($example, 'it_IT');
echo $obj->getPhrase(); // Ho un can3 e ûn g#4*Tt(_______) IN Cå$...aα Chë lîTigano sémpr€ CØL MIÕ Ç/-\n......€! AΑα
ehco $obj->filter(); // Ho un ### e ûn ### IN ###α Chë lîTigano sémpr€ CØL MIÕ ###! AΑα
```

## License

Code licensed under the [MIT License](https://github.com/BlorisL/php-tsuruya/blob/main/LICENSE).

Do whatever you want with this code, but remember opensource projects work with the help of the community so would be really useful if any errors, updates, features or ideas were reported.

[Share with me a cup of tea](https://www.buymeacoffee.com/bloris) ☕
