<?php
declare(strict_types=1);
namespace Glued\Core\Classes\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class EmailAvailableException extends ValidationException
{
    public static $defaultTemplates;

    public function __construct() {
        self::$defaultTemplates = [
            self::MODE_DEFAULT => [
                self::STANDARD => __('Email is already taken.'),
            ],
        ];
    }
}