<?php
/**
 * Validator - Centralized input validation
 *
 * Provides methods for validating common input types:
 * - Email addresses
 * - URLs
 * - Integers (with range)
 * - Strings (with length, pattern)
 * - Files (extension, size)
 * - Dates
 */

class Validator {

    /**
     * Errors from last validation
     * @var array
     */
    private static $errors = [];

    /**
     * Validate email address
     *
     * @param string $email
     * @return bool
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     *
     * @param string $url
     * @return bool
     */
    public static function isValidUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate positive integer
     *
     * @param mixed $value
     * @param int $min
     * @param int|null $max
     * @return bool
     */
    public static function isValidInt($value, $min = 1, $max = null) {
        $options = ['min_range' => $min];
        if ($max !== null) {
            $options['max_range'] = $max;
        }

        return filter_var($value, FILTER_VALIDATE_INT, ['options' => $options]) !== false;
    }

    /**
     * Validate string length
     *
     * @param string $str
     * @param int $minLength
     * @param int|null $maxLength
     * @return bool
     */
    public static function isValidStringLength($str, $minLength = 1, $maxLength = null) {
        $length = mb_strlen($str, 'UTF-8');

        if ($length < $minLength) {
            return false;
        }

        if ($maxLength !== null && $length > $maxLength) {
            return false;
        }

        return true;
    }

    /**
     * Validate string matches pattern
     *
     * @param string $str
     * @param string $pattern
     * @return bool
     */
    public static function isValidPattern($str, $pattern) {
        return preg_match($pattern, $str) === 1;
    }

    /**
     * Validate file extension
     *
     * @param string $filename
     * @param array $allowedExtensions
     * @return bool
     */
    public static function isValidFileExtension($filename, $allowedExtensions = []) {
        if (empty($allowedExtensions)) {
            return true;
        }

        $pathInfo = pathinfo($filename);
        $extension = strtolower($pathInfo['extension'] ?? '');

        return in_array($extension, array_map('strtolower', $allowedExtensions));
    }

    /**
     * Validate file size
     *
     * @param int $fileSize
     * @param int $maxSize
     * @return bool
     */
    public static function isValidFileSize($fileSize, $maxSize = 10485760) {
        return $fileSize > 0 && $fileSize <= $maxSize;
    }

    /**
     * Validate date format
     *
     * @param string $date
     * @param string $format
     * @return bool
     */
    public static function isValidDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate JSON string
     *
     * @param string $json
     * @return bool
     */
    public static function isValidJson($json) {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate username (alphanumeric, underscore, dash, 3-32 chars)
     *
     * @param string $username
     * @return bool
     */
    public static function isValidUsername($username) {
        return preg_match('/^[a-zA-Z0-9_\-]{3,32}$/', $username) === 1;
    }

    /**
     * Validate password strength
     *
     * @param string $password
     * @param int $minLength
     * @return bool
     */
    public static function isValidPassword($password, $minLength = 8) {
        if (strlen($password) < $minLength) {
            return false;
        }

        // At least one uppercase, one lowercase, one number
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        return true;
    }

    /**
     * Validate phone number (basic international format)
     *
     * @param string $phone
     * @return bool
     */
    public static function isValidPhone($phone) {
        return preg_match('/^\+?[1-9]\d{1,14}$/', preg_replace('/\D/', '', $phone)) === 1;
    }

    /**
     * Validate hexadecimal string
     *
     * @param string $hex
     * @return bool
     */
    public static function isValidHex($hex) {
        return preg_match('/^[0-9a-f]+$/i', $hex) === 1;
    }

    /**
     * Batch validate multiple values
     *
     * @param array $values Array with [key => [validator, args], ...]
     * @return bool
     *
     * Example:
     * $values = [
     *     'email' => ['isValidEmail', $email],
     *     'age' => ['isValidInt', [$age, 1, 120]],
     * ];
     * Validator::validateBatch($values);
     */
    public static function validateBatch($values) {
        self::$errors = [];

        foreach ($values as $key => $validation) {
            if (!is_array($validation) || count($validation) < 2) {
                self::$errors[$key] = 'Invalid validation configuration';
                continue;
            }

            [$method, $args] = $validation;

            if (!method_exists(self::class, $method)) {
                self::$errors[$key] = "Validation method not found: $method";
                continue;
            }

            // Convert single argument to array
            if (!is_array($args)) {
                $args = [$args];
            }

            // Call validator method
            if (!call_user_func_array([self::class, $method], $args)) {
                self::$errors[$key] = ucfirst($key) . ' is invalid';
            }
        }

        return empty(self::$errors);
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public static function getErrors() {
        return self::$errors;
    }

    /**
     * Clear validation errors
     */
    public static function clearErrors() {
        self::$errors = [];
    }
}
