<?php

namespace Glued\Core\Classes\Validation;
use Respect\Validation\Validator as Respect;
use Respect\Validation\Exceptions\NestedValidationException;

/**
 * Validator class simplifies usage of the Respect\Validation
 * and makes it easier to write custom rules. See example usage
 * in AuthController::signup_post().
 * 
 * @example 
 * AuthController::signup_get() shows a twig template containing
 * a form with the 'email', 'name', 'password' fields. Posted data
 * are validated and processed by AuthController::signup_post()
 * 
 * Note that to define the $rules array, the class Respect\Validator
 * must be pulled in with `use Respect\Validation\Validator as v;`
 * in the AuthController.
 *
 * NOTE that new rules (and relevant exceptions) are located under
 * Core\Classes\Validation\{Rules,Exceptions}\* - telling Respect 
 * where to look for these is done in Core\bootstrap.php
 */
class Validator
{

    protected $errors;
    protected $reseed;

    /**
     * validate() looks at get/post parameters passed via $request
     * and validates them against Respect\Validator style rules
     * described in the array $rules. Errors are sent back to users
     * via $_SESSION['validation_errors']
     */
    public function validate($request, array $rules)
    {
        foreach ($rules as $field => $rule) {
            // Loop over the $rules array. Get data with $request->getParam().
            // Whenever an exception comes up, append the validation failure 
            // to the $errors array. Make output nicer by capitalizing first
            // letters with ucfirst(). Pass errors via session back to users.
            try {
                $rule->setName(ucfirst($field))->assert($request->getParam($field));
            } catch (NestedValidationException $e) {
                $this->errors[$field] = $e->getMessages();
            }
            $_SESSION['validation_errors'] = $this->errors;
        }
        return $this;
    }

    public function reseed($request, array $reseed)
    {
        foreach ($reseed as $item) {
            $this->reseed[$item] = $request->getParam($item);
        }
        // Don't do this here:
        // $this->reseed = $request->getParams(); 
        // We don't really want to send, i.e., the password back
        // While it shouldn't matter over secure connections, why add surface?
        return $this->reseed;
    }

    /** 
     * returns true|false if validation failed|passed
     */
    public function failed()
    {
        return !empty($this->errors);
    }

    /** 
     * returns the validation error messages array
     */    
    public function messages()
    {
        return $this->errors;
    }
}