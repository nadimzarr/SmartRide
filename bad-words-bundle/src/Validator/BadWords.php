<?php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class BadWords extends Constraint
{
    public $message = 'Votre message contient des mots interdits : "{{ word }}"';
}