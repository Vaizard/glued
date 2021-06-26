<?php
declare(strict_types=1);
namespace Glued\Core\Classes\Validation\Rules;

use Glued\Core\Controllers\AbstractController as c;
use Respect\Validation\Rules\AbstractRule;

/**
 * This class extends the Respect\Validation\Rules\AbstractRule
 * by checking if an email address can be used to register a
 * new glued account (it must be available for the registration
 * to complete).
 *
 * Extending the AbstractRule requires defining a validate() infunction.
 * The new rule available in Respect\Validation will assume the class name
 * (EmailAvailable in this case)
 *
 * Since we're testing e-mails against the database, this rule will need
 * a database connection pointer when called. Typically, this will mean
 * EmailAvailable($this->db).
 *
 * Used in \Glued\Core\Controllers\AuthController:signup_post()
 */
class EmailAvailable extends AbstractRule
{

    protected $db;
    public function __construct($db) 
    {
        $this->db = $db;
    }
    
    public function validate($input)
    {
        $this->db->where('c_email', $input);
        if ($this->db->getOne("t_core_users")) {
            return false; // validation failed, e-mail already fonud in database
        } else { 
            return true; // validation passed, e-mail can be used to set up a new account
        }
    }
}