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

        // Convertir le texte en minuscules pour la comparaison
        $texteLowercase = mb_strtolower($value);

        foreach ($this->filter->getBadWords() as $word) {
            // Créer un pattern qui vérifie les mots entiers uniquement
            // \b = limite de mot (word boundary)
            $pattern = '/\b' . preg_quote(mb_strtolower($word), '/') . '\b/u';
            
            // Vérifier si le mot entier existe
            if (preg_match($pattern, $texteLowercase)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ word }}', $word)
                    ->addViolation();
                return;
            }
        }
    }
}