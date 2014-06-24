cakephp-timezone
======

CakePHP model behavior for automating timezone functionality. Also supports virtual date fields which can be formatted using PHP date formatting conventions. Support requests and bugs will most likely not be responded to at this stage.

Dependencies
------
This behavior assumes you are using the CakePHP Auth component to manage user authorisation. It also assumes you have a field called 'timezone' in your users table which contains a timezone in the PHP format eg. Australia/Brisbane. Without this, the behaviour will fail to work correctly, this may be revised in the future but suits my needs for now.

Installation
------
Place the TimeZoneBehavior.php file in your CakePHP model behaviors folder aka. /app/Model/Behavior/

Demo
------
For minimal usage, you only need to include the fields section with a list of fields to include
```php
	public $actsAs = array(
		"TimeZone" => array(
			"fields" => array(
				"created",
				"modified"
			)
		)
	);
```

The full API including optional fields and virtual fields is used as below
```php
	public $actsAs = array(
		"TimeZone" => array(
			"fields" => array(
				"created",
				"modified",
				"start" => array(
					"optional" => true
				)
			),
			"virtualFields" => array(
				"start_iso" => array(
					"format" => "c",
					"source" => "start"
				)
			)
		)
	);
```

Licensing
------
Code is licensed under the MIT License.

Developer
------
Jayden Meyer http://jaydenm.com/