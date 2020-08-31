# PHP-Icons8
PHP SDK for running queries against the millions of icons provided by
[Icons8](https://icons8.com). Includes recursive searches.

### Supports
- Searches

### Sample Search
``` php
$client = new onassar\Icons8\Icons8();
$client->setAPIKey('***');
$client->setLimit(10);
$client->setOffset(0);
$results = $client->search('love') ?? array();
print_r($results);
exit(0);
```

### Note
Requires
[PHP-RemoteRequests](https://github.com/onassar/PHP-RemoteRequests).
