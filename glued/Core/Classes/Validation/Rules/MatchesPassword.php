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
    protected $user_id;
    protected $auth_id;
    public function __construct($db) 
    {
        $this->db = $db;
        $this->user_id = $user_id;
        $this->auth_id = $auth_id;
    }
    
    public function validate($input)
    {
        $this->db->where('c_type', 1);
        $this->db->where('c_user_id', $this->user_id);
        $this->db->where('c_uid', $this->auth_id);
        $this->db->where('c_email', $input);
        if ($this->db->getOne("t_core_users")) {
            return password_verify($input, $user_data['c_pasword']);
        } else { 
            return false;
        }
    }
}