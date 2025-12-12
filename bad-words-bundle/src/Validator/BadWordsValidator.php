<?php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Vendor\BadWordsBundle\Service\BadWordsFilter;

class BadWordsValidator extends ConstraintValidator
{
    private BadWordsFilter $filter;

    public function __construct(BadWordsFilter $filter) {
        $this->filter = $filter;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$value) return;

        foreach ($this->filter->getBadWords() as $word) {
            if (stripos($value, $word) !== false) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ word }}', $word)
                    ->addViolation();
                return;
            }
        }
    }
}
