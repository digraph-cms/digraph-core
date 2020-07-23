<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\FieldInterface;
use Formward\Fields\Checkbox;
use Formward\Fields\Container;
use Formward\Fields\Input;

class User extends Input
{
    protected $cms;

    public function __construct(string $label, string $name=null, FieldInterface $parent=null, $cms=null)
    {
        parent::__construct($label, $name, $parent);
        $this->cms = $cms;
        $this->addTip('Enter a valid user identifier, such as an email address or system identifier','entervalid');
        $this->addValidatorFunction(
            'validuser',
            function($field) {
                if ($field->submittedValue()) {
                    if (!$this->cms->helper('users')->search($field->submittedValue())) {
                        return 'User not found';
                    }
                }
                return true;
            }
        );
    }

    protected function userID($value)
    {
        if (!$value) {
            return null;
        }
        if ($user = $this->cms->helper('users')->search($value)) {
            $user = array_pop($user);
            return $user->id();
        }
        return $value;
    }

    public function value($set = null) {
        return $this->userID(
            parent::value($set)
        );
    }

    public function default($set = null) {
        return $this->userID(
            parent::default($set)
        );
    }
}
