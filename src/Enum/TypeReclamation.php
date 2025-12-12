<?php

namespace App\Enum;

enum TypeReclamation: string
{
    case INCIDENT = 'Incident';
    case RETARD = 'Retard';
    case COMPORTEMENT_CONDUCTEUR = 'Comportement du conducteur';
    case PROPRETE_VEHICULE = 'Propreté du véhicule';
    case ANNULATION_TRAJET = 'Annulation de trajet';
    case PROBLEME_PAIEMENT = 'Problème de paiement';
    case AUTRE = 'Autre';
}