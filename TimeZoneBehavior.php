<?php
/**
 * This behavior provides automatic timezone conversion functionality for models
 * @author Jayden Meyer
 * @package TimeZoneBehaviour
 * @link https://github.com/drjaydenm/cakephp-timezone
 * @since version 1.0
Copyright (c) 2014 Jayden Meyer

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/
class TimeZoneBehavior extends modelBehavior {
	public $timeZoneOptions = array(
		'fields' => array(),
		'virtualFields' => array(),
		'timezone' => "UTC"
	);

	public function setup(Model $model, $config = array()) {
		if (!isset($this->options[$model->alias])) {
			$this->options[$model->alias] = $this->timeZoneOptions;
		}
		if (!is_array($config)) {
			$config = array();
		}
		$this->options[$model->alias] = array_merge($this->options[$model->alias], $config);

		// If we have not been provided with a timezone, try and find the timezone info in the current session, else use the default
		if (!isset($config['timezone'])) {
			App::uses("Util", "CakeSession");
			$user = CakeSession::read("Auth.User");
			if ($user) {
				if (isset($user['timezone'])) {
					$this->options[$model->alias]['timezone'] = $user['timezone'];
				}
			}
		}
	}

	public function afterFind(Model $model, $results, $primary = false) {
		if ($primary) {
			if (count($this->options[$model->alias]['fields'])) {
				foreach ($this->options[$model->alias]['fields'] as $field => $fieldOptions) {
					for ($i = 0; $i < count($results); $i++) {
						// Check if the field is an index or the actual field name
						if (is_int($field)) {
							$field = $fieldOptions;
						}

						// If the field exists in the data
						if (isset($results[$i][$model->alias][$field])) {
							$type = "";
							// Look for the type of the field in the validation data
							if (isset($model->validate[$field])) {
								if (isset($model->validate[$field]['rule'])) {
									$type = $model->validate[$field]['rule'];
								}
							}

							// If we still havent found the type, check if it is
							// the default created or modified datetime
							if (!$type) {
								if ($field == "created" || $field == "modified") {
									$type = "datetime";
								}
							}

							if ($type == "datetime") {
								$value = $results[$i][$model->alias][$field];

								$date = DateTime::createFromFormat("Y-m-d H:i:s", $value, new DateTimeZone("UTC"));

								// Check that the date is valid and throw an error if it isnt
								if ($date) {
									$date->setTimeZone(new DateTimeZone($this->options[$model->alias]['timezone']));

									$value = $date->format("d/m/Y h:i A");

									$results[$i][$model->alias][$field] = $value;
								} else if (!isset($fieldOptions['optional']) || !$fieldOptions['optional']) {
									throw new Exception("Could not convert the non-optional datetime");
								}
							}
						}
					}
				}
			}

			// Handle the creation of virtual fields
			if (count($this->options[$model->alias]['virtualFields'])) {
				foreach ($this->options[$model->alias]['virtualFields'] as $fieldKey => $fieldValue) {
					// Check if the field is an index or the actual field name
					if (is_int($fieldKey)) {
						$fieldKey = $fieldOptions;
					}

					// Check we have the needed settings
					if (!isset($fieldValue['format']) || !isset($fieldValue['source'])) {
						continue;
					}

					for ($i = 0; $i < count($results); $i++) {
						// If the source field exists in the data then continue
						if (isset($results[$i][$model->alias][$fieldValue['source']])) {
							$source = $results[$i][$model->alias][$fieldValue['source']];
							$date = DateTime::createFromFormat("d/m/Y h:i A", $source, new DateTimeZone($this->options[$model->alias]['timezone']));
							
							// Check that the date is valid and throw an error if it isnt
							if ($date) {
								$source = $date->format($fieldValue['format']);

								$results[$i][$model->alias][$fieldKey] = $source;
							} else if (!isset($this->options[$model->alias]['fields'][$source]['optional']) || !$this->options[$model->alias]['fields'][$source]['optional']) {
								throw new Exception("Could not convert the non-optional datetime");
							}
						}
					}
				}
			}
		}

		return $results;
	}

	public function beforeValidate(Model $model, $options = array()) {
		// If we have been provided datetime fields, process them
		if (count($this->options[$model->alias]['fields'])) {
			foreach ($this->options[$model->alias]['fields'] as $field => $fieldOptions) {
				// Check if the field is an index or the actual field name
				if (is_int($field)) {
					$field = $fieldOptions;
				}

				// If the field exists in the data
				if (isset($model->data[$model->alias][$field])) {
					$type = "";
					// Look for the type of the field in the validation data
					if (isset($model->validate[$field])) {
						if (isset($model->validate[$field]['rule'])) {
							$type = $model->validate[$field]['rule'];
						}
					}

					// If we still havent found the type, check if it is
					// the default created or modified datetime
					// If it is the modified time, set it manually to now
					if (!$type) {
						if ($field == "created") {
							$type = "datetime";
						} else if ($field == "modified") {
							$date = new DateTime();
							$model->data[$model->alias][$field] = $date->format("Y-m-d H:i:s");
							continue;
						}
					}

					if ($type == "datetime") {
						$value = $model->data[$model->alias][$field];

						$date = DateTime::createFromFormat("d/m/Y h:i A", $value, new DateTimeZone($this->options[$model->alias]['timezone']));

						// Check that the date is valid and throw an error if it isnt
						if ($date) {
							$date->setTimeZone(new DateTimeZone("UTC"));

							$value = $date->format("Y-m-d H:i:s");

							$model->data[$model->alias][$field] = $value;
						} else if (!isset($fieldOptions['optional']) || !$fieldOptions['optional']) {
							throw new Exception("Could not convert the non-optional datetime");
						}
					}
				}
			}
		}

		return true;
	}
}