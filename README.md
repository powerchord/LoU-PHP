LoU-PHP
=======

#### Version 0.1.1

## Description

LoU-PHP is an opensourced class that provides basic methods for connecting, authenticating, and retrieving data from [Lord of Ultima](http://www.lordofultima.com).

## Requirements

LoU-PHP requires the [Simple HTML DOM Parser](http://simplehtmldom.sourceforge.net/) library which is included in this GitHub project directory for your convenience. The file must be located within the same directory as the classfile.

## Usage

Before you start, you need to

* create a writable directory for storage of cookie files
* know the LoU account info (email, password, world id)

### Connecting to LoU

This class is extremely basic providing only the minimal methods needed for connecting to LoU and retrieving data from the LoU API. To login to a LoU server:

```php

$lou = LoU::createClient('path/to/cookie/directory');
$lou->login('myemail@domain.com', 'mypassword');
```

This will authenticate with LoU and populate a list of available servers. That server list can be shown like this:

```php

foreach( $lou->server_list as $world )
{
    echo 'ID: ' . $world->id . '<br>';
    echo 'Name: ' . $world->name . '<br>';
    echo 'URL: ' . $world->server . '<br>';
}
```

There are several ways of selecting a LoU world and retrieving a valid session key. This allows you to connect to a world with one simple step:

```php

// Method 1
$lou = LoU::createClient('path/to/cookie/directory');
$lou->setWord(74)->login('myemail@domain.com', 'mypassword')

// Method 2
$lou = LoU::createClient('path/to/cookie/directory');
$lou->login('myemail@domain.com', 'mypassword')->selectWorld(74);
```

Or you can first login and display a list of worlds for a user to choose from and then select the world:

````php

$lou = LoU::createClient('path/to/cookie/directory');
$lou->login('myemail@domain.com', 'mypassword');

foreach( $lou->server_list as $world )
{
    echo 'ID: ' . $world->id . '<br>';
    echo 'Name: ' . $world->name . '<br>';
    echo 'URL: ' . $world->server . '<br>';
}

// Controls for user selection here

$lou->selectWorld($user_choice);
```

### Retrieving Data

There are two methods for retrieving data from LoU. The first method involves providing an EndPoint and any necessary data:

```php

// Retrieve public information for the given player id
$data = $lou->get( 'GetPublicPlayerInfo', array( 'id' => '1234567' ) );

// Retrieve public information for the given city id
$data = $lou->get( 'GetPublicCityInfo', array( 'id' => '1234567' ) );
```

This method is for retrieving information via the Poll EndPoint:

```php

// Retrieve current list of incoming attacks
$data = $lou->poll( array( 'ALL_AT' => '' ) );

// Retrieve private information of the authenticated user's city
$data = $lou->poll( array( 'CITY' => '1234567' ) );

// Retrieve time information from the server
$data = $lou->poll( array( 'TIME' => time() ) );
```

### Player Class

You can now easily retrieve formatted data for the authenticated player or by a given ID

```php

// Connect & Authenticate to LoU
$lou = LoU::createClient('path/to/cookie/directory');
$lou->login('myemail@domain.com', 'mypassword')->selectWorld(74);

// Retrieve our own data
$my_player = new Player();
$my_data = $my_player->getData();

// Retrieve somebody else's data with the ID of 1000
$other_player = new Player(1000);
$other_data = $other_player->getData();
```

## What's Next?

There are many endpoints used in LoU for sending commands and retrieving data. I'll leave that for you to explore. I do plan on extending this library with more classes providing simple methods for retrieving data.

### Todo

I have not been able to decode the data needed to request World Map info. If anybody can help me figure this out, I would be forever grateful. From my best understanding it is encoded using basE91 LE with the following cipher:

```php

$cipher = array (
    'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7, 'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17,
    'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23, 'Y' => 24, 'Z' => 25, 'a' => 26, 'b' => 27, 'c' => 28, 'd' => 29, 'e' => 30, 'f' => 31, 'g' => 32, 'h' => 33, 'i' => 34,
    'j' => 35, 'k' => 36, 'l' => 37, 'm' => 38, 'n' => 39, 'o' => 40, 'p' => 41, 'q' => 42, 'r' => 43, 's' => 44, 't' => 45, 'u' => 46, 'v' => 47, 'w' => 48, 'x' => 49, 'y' => 50, 'z' => 51,
    '0' => 52, '1' => 53, '2' => 54, '3' => 55, '4' => 56, '5' => 57, '6' => 58, '7' => 59, '8' => 60, '9' => 61, '!' => 62, '#' => 63, '$' => 64, '%' => 65, '&' => 66, '(' => 67, ')' => 68,
    '*' => 69, '+' => 70, ',' => 71, '.' => 72, ' ' => 73, ':' => 74, ';' => 75, '<' => 76, '=' => 77, '>' => 78, '?' => 79, '@' => 80, '[' => 81, ']' => 82, '^' => 83, '_' => 84, '`' => 85,
    '{' => 86, '|' => 87, '}' => 88, '~' => 89, '\'' => 90
);
```

### Changelog

* 10/14/12 - Converted class to singleton pattern in preperation for additional classes.

## License

Copyright 2012, Roger Mayfield

This library is released under the [GNU General Public License](http://opensource.org/licenses/gpl-3.0.html)

## Disclaimer

Although using this class for data retrieval is not prohibited by the [Lord of Ultima Terms of Service](http://www.lordofultima.com/en/game/rules), it is against the rules to automate the game using the methods contained within it. You are liable for your own applications of this library. I am not responsible for any bans that may occur.