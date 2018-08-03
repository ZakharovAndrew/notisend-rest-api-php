# NotiSend REST client library
A simple NotiSend REST client library and example for PHP.

API Documentation https://notisend.ru/dev/email/api/

### Usage

```php
<?php
include 'ApiClient.php';

try {
    $ApiClient = new ApiClient('TOKEN');
    //create test group
    var_dump($ApiClient->createGroup('TEST1'));
    //get list group
    var_dump($ApiClient->listGroup());
    //get info about group with ID 12345
    var_dump($ApiClient->listInfo(12345));
} catch (Exception $e) {
    print $e->getLine() . ' : ' . $e->getMessage() . PHP_EOL;
    exit();
}
```
## License

[MIT](https://github.com/ZakharovAndrew/php-ftp-client/blob/master/LICENSE) c) 2018, Zakharov Andrew <https://github.com/ZakharovAndrew>.
