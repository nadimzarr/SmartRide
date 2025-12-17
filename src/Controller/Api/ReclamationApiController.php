<?php

namespace App\Controller\Api;

use App\Entity\Reclamation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/reclamation')]
class ReclamationApiController extends AbstractController
{
    private $httpClient;
    private $entityManager;

    public function __construct(HttpClientInterface $httpClient, EntityManagerInterface $entityManager)
    {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
    }

    #[Route('/generer-reponse/{id}', name: 'api_reclamation_generer_reponse', methods: ['POST'])]
    public function genererReponse(int $id): JsonResponse
    {
        try {
            $reclamation = $this->entityManager->getRepository(Reclamation::class)->find($id);
            
            if (!$reclamation) {
                return $this->json([
                    'success' => false,
                    'message' => 'Réclamation non trouvée'
                ], 404);
            }

            if ($reclamation->getReponse() !== null) {
                return $this->json([
                    'success' => false,
                    'message' => 'Cette réclamation a déjà une réponse'
                ], 400);
            }

            // ⭐ STRATÉGIE VARIÉE : 60% IA, 40% templates
            $useAI = !empty($_ENV['GEMINI_API_KEY']) && (rand(1, 100) > 40);
            
            if ($useAI) {
                $reponseGeneree = $this->genererReponseIA($reclamation);
            } else {
                $reponseGeneree = $this->genererReponseTemplate($reclamation);
            }

            return $this->json([
                'success' => true,
                'reponse' => $reponseGeneree,
                'reclamation_id' => $id,
                'nom_client' => $reclamation->getNom() . ' ' . $reclamation->getPrenom(),
                'type_probleme' => $reclamation->getTypeReclamation()->value
            ]);

        } catch (\Exception $e) {
            // Fallback élégant
            $reclamation = $this->entityManager->getRepository(Reclamation::class)->find($id);
            $reponseFallback = $this->genererReponseTemplate($reclamation ?? null);
            
            return $this->json([
                'success' => true,
                'reponse' => $reponseFallback,
                'reclamation_id' => $id,
                'message' => 'Génération IA échouée, template utilisé'
            ]);
        }
    }

    private function genererReponseIA(Reclamation $reclamation): string
    {
        try {
            $apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
            
            if (!$apiKey) {
                return $this->genererReponseTemplate($reclamation);
            }

            $prompt = $this->construirePromptCible($reclamation);

            $response = $this->httpClient->request('POST', 
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'text' => $prompt
                                    ]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.8, // Bon équilibre créativité/cohérence
                            'topK' => 40,
                            'topP' => 0.9,
                            'maxOutputTokens' => 800,
                        ]
                    ],
                    'timeout' => 15
                ]
            );

            $data = $response->toArray();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $reponse = trim($data['candidates'][0]['content']['parts'][0]['text']);
                return $this->formaterReponse($reponse, $reclamation);
            }

            return $this->genererReponseTemplate($reclamation);

        } catch (\Exception $e) {
            return $this->genererReponseTemplate($reclamation);
        }
    }

    private function construirePromptCible(Reclamation $reclamation): string
    {
        $nom = $reclamation->getNom();
        $prenom = $reclamation->getPrenom();
        $type = $reclamation->getTypeReclamation()->value;
        $message = $reclamation->getMessage();
        
        // ⭐ CONTEXTE SPÉCIFIQUE PAR TYPE
        $contexteType = $this->getContexteDetaille($type);
        
        // ⭐ RÔLE VARIABLE
        $roles = [
            "Expert en support client pour une plateforme de covoiturage",
            "Agent de résolution de conflits SmartRide",
            "Responsable service client avec expertise covoiturage",
            "Conseiller en médiation transport partagé"
        ];
        
        $prompt = "RÔLE : " . $roles[array_rand($roles)] . "\n\n";
        $prompt .= "OBJECTIF : Générer une réponse UNIQUE et PERSONNALISÉE\n\n";
        $prompt .= "CONTEXTE DU PROBLÈME :\n";
        $prompt .= "• Type : {$type}\n";
        $prompt .= "• Détails : {$contexteType}\n";
        $prompt .= "• Client : {$prenom} {$nom}\n";
        $prompt .= "• Description : {$message}\n\n";
        
        $prompt .= "EXIGENCES CRITIQUES :\n";
        $prompt .= "1. JAMAIS copier de réponses précédentes\n";
        $prompt .= "2. Formulations ORIGINALES à chaque génération\n";
        $prompt .= "3. Adapter le TON au type de problème\n";
        $prompt .= "4. Proposer des solutions SPÉCIFIQUES au covoiturage\n";
        $prompt .= "5. Inclure des ÉLÉMENTS CONCRETS (délais, montants, procédures)\n\n";
        
        $prompt .= "STRUCTURE SUGGÉRÉE (à varier) :\n";
        $prompt .= "• Salutation personnalisée\n";
        $prompt .= "• Reconnaissance du problème\n";
        $prompt .= "• Analyse brève\n";
        $prompt .= "• Solution(s) proposée(s)\n";
        $prompt .= "• Délais concrets\n";
        $prompt .= "• Compensation si applicable\n";
        $prompt .= "• Engagement de suivi\n";
        $prompt .= "• Formule de politesse\n\n";
        
        $prompt .= "TON À ADAPTER :\n";
        $prompt .= $this->getTonParType($type);
        
        return $prompt;
    }

    private function getContexteDetaille(string $type): string
    {
        $contextes = [
            'Incident' => [
                "Incident survenu pendant un trajet de covoiturage (accident, panne, problème mécanique)",
                "Situation imprévue affectant la sécurité ou le bon déroulement du trajet",
                "Événement inattendu nécessitant une intervention immédiate"
            ],
            'Retard' => [
                "Retard important du conducteur au point de rendez-vous",
                "Trajet plus long que prévu affectant les plans du passager",
                "Problème de ponctualité impactant l'expérience client"
            ],
            'Comportement du conducteur' => [
                "Conduite dangereuse, comportement inapproprié ou manque de professionnalisme",
                "Attitude du conducteur ne respectant pas les règles de la plateforme",
                "Problème relationnel ou manque de courtoisie pendant le trajet"
            ],
            'Propreté du véhicule' => [
                "Véhicule sale, malodorant ou mal entretenu",
                "Conditions d'hygiène inacceptables pour un service payant",
                "Manque de propreté affectant le confort du passager"
            ],
            'Annulation de trajet' => [
                "Annulation tardive par le conducteur sans justification valable",
                "Trajet annulé à la dernière minute causant des désagréments",
                "Problème d'annulation non justifiée ou trop fréquente"
            ],
            'Problème de paiement' => [
                "Double prélèvement, montant incorrect ou transaction non validée",
                "Problème technique ou fraude sur le paiement en ligne",
                "Litige financier nécessitant vérification et correction"
            ],
            'Autre' => [
                "Problème divers ne rentrant pas dans les catégories standards",
                "Situation particulière nécessitant une approche personnalisée",
                "Demande ou réclamation spécifique au service SmartRide"
            ]
        ];
        
        return $contextes[$type][array_rand($contextes[$type])] ?? "Réclamation concernant le service de covoiturage";
    }

    private function getTonParType(string $type): string
    {
        return match($type) {
            'Incident' => "Ton : URGENT et EMPATHIQUE. Exprimer une préoccupation réelle pour la sécurité.",
            'Retard' => "Ton : COMPRÉHENSIF mais PROFESSIONNEL. Reconnaître l'impact sur l'emploi du temps.",
            'Comportement du conducteur' => "Ton : FERME et DÉCISIF. Montrer une tolérance zéro pour les mauvais comportements.",
            'Propreté du véhicule' => "Ton : DÉSOLÉ et PROACTIF. Insister sur les standards de qualité.",
            'Annulation de trajet' => "Ton : REGRETTABLE mais SOLUTIONNÉ. Proposer des alternatives concrètes.",
            'Problème de paiement' => "Ton : TECHNIQUE et RASSURANT. Démontrer un processus de résolution clair.",
            default => "Ton : PROFESSIONNEL et EMPATHIQUE. Équilibre entre courtoisie et efficacité."
        };
    }

    private function genererReponseTemplate(Reclamation $reclamation): string
    {
        $nom = $reclamation->getNom();
        $prenom = $reclamation->getPrenom();
        $type = $reclamation->getTypeReclamation()->value;
        
        // ⭐ SÉLECTION ALEATOIRE PARMI PLUSIEURS TEMPLATES
        $templates = $this->getTemplatesParType($type);
        $template = $templates[array_rand($templates)];
        
        // Variables dynamiques
        $compensations = ['10 TND', '15 TND', '20% de réduction', 'un trajet gratuit', 'un crédit de 25 TND'];
        $delais = ['24 heures', '48 heures', '3 jours ouvrables', 'sous 72h'];
        $contacts = ['notre service client', 'un responsable dédié', 'notre équipe de médiation'];
        
        $reponse = str_replace(
            ['{NOM}', '{PRENOM}', '{COMPENSATION}', '{DELAI}', '{CONTACT}'],
            [
                $nom, 
                $prenom,
                $compensations[array_rand($compensations)],
                $delais[array_rand($delais)],
                $contacts[array_rand($contacts)]
            ],
            $template
        );
        
        // ⭐ AJOUT D'UNE PHRASE UNIQUE POUR CHAQUE RÉPONSE
        $phrasesUniques = [
            "\n\nNous accordons une attention particulière à chaque retour client.",
            "\n\nVotre expérience nous aide à améliorer continuellement nos services.",
            "\n\nSmartRide s'engage à garantir votre satisfaction.",
            "\n\nNous prenons cet incident très au sérieux.",
            "\n\nNotre priorité est de rétablir votre confiance.",
            "\n\nNous mettons tout en œuvre pour éviter que cela ne se reproduise."
        ];
        
        $reponse .= $phrasesUniques[array_rand($phrasesUniques)];
        
        return $reponse;
    }

    private function getTemplatesParType(string $type): array
    {
        return match($type) {
            'Incident' => [
                "Cher(e) {PRENOM},\n\nNous avons pris connaissance avec grande attention de l'incident survenu lors de votre trajet. Nous vous présentons nos sincères excuses pour cette situation.\n\nNotre équipe sécurité va investiguer cet incident et prendre contact avec le conducteur concerné. Pour compenser ce désagrément, nous vous offrons {COMPENSATION} sur votre prochain trajet.\n\nUn retour vous sera fourni sous {DELAI}.\n\nCordialement,\nL'équipe SmartRide",
                
                "Bonjour M./Mme {NOM},\n\nL'incident que vous avez rencontré est inacceptable et nous en sommes profondément désolés. La sécurité de nos utilisateurs est notre priorité absolue.\n\nNous allons :\n1. Suspendre temporairement le conducteur\n2. Examiner les circonstances de l'incident\n3. Vous proposer une compensation adaptée\n\n{CONTACT} vous contactera très prochainement.\n\nSincèrement,\nService Client SmartRide",
                
                "Madame, Monsieur {NOM},\n\nVotre signalement concernant l'incident pendant votre covoiturage nous a immédiatement alertés. Nous regrettons vivement cette expérience.\n\nNotre service d'urgence a été notifié et prendra les mesures nécessaires. En attendant, nous vous créditons de {COMPENSATION} sur votre compte.\n\nNous nous excusons pour ce désagrément.\n\nL'équipe Support"
            ],
            
            'Retard' => [
                "{PRENOM}, bonjour,\n\nNous comprenons votre frustration concernant le retard important que vous avez subi. Ce manque de ponctualité ne correspond pas à nos standards.\n\nLe conducteur concerné recevra un avertissement et nous ajusterons sa fiabilité dans notre algorithme. Pour vous dédommager, nous vous offrons {COMPENSATION}.\n\nNos excuses pour les désagréments occasionnés.\n\nBien à vous,\nSmartRide",
                
                "Cher(e) client(e),\n\nMerci de nous avoir signalé ce retard. Nous regrettons l'impact que cela a eu sur votre emploi du temps.\n\nNous allons revoir le système d'évaluation des conducteurs pour améliorer la ponctualité. En geste de bonne volonté, veuillez accepter {COMPENSATION}.\n\nCordialement,\nL'équipe Qualité"
            ],
            
            'Comportement du conducteur' => [
                "Bonjour {PRENOM},\n\nLe comportement que vous décrivez est totalement inacceptable. Nous présentons nos plus sincères excuses pour cette expérience.\n\nLe conducteur sera immédiatement suspendu pendant notre enquête. Nous vous offrons {COMPENSATION} en compensation.\n\n{CONTACT} vous contactera pour recueillir plus de détails.\n\nAvec nos regrets,\nService Conduite Responsable",
                
                "M./Mme {NOM},\n\nNous prenons très au sérieux votre signalement concernant le comportement inapproprié du conducteur. Ce type d'attitude n'a pas sa place sur notre plateforme.\n\nActions immédiates :\n• Enquête interne lancée\n• Conducteur retiré temporairement\n• Compensation de {COMPENSATION}\n\nNous vous remercions de votre vigilance.\n\nRespectueusement,\nSmartRide Éthique"
            ],
            
            'Propreté du véhicule' => [
                "{PRENOM}, bonjour,\n\nNous sommes désolés d'apprendre que le véhicule ne répondait pas à nos standards de propreté. Cela ne devrait jamais arriver.\n\nNous allons :\n1. Rappeler au conducteur ses obligations\n2. Vérifier ses évaluations précédentes\n3. Vous offrir {COMPENSATION}\n\nMerci de votre compréhension.\n\nCordialement,\nService Qualité SmartRide",
                
                "Cher(e) client(e),\n\nVotre retour sur l'état du véhicule nous est précieux. Nous insistons auprès de tous nos conducteurs sur l'importance de la propreté.\n\nEn compensation, veuillez accepter {COMPENSATION} pour votre prochain trajet.\n\nNous ferons un suivi avec le conducteur concerné.\n\nSincèrement,\nL'équipe SmartRide"
            ],
            
            'Annulation de trajet' => [
                "Bonjour {PRENOM},\n\nNous regrettons vivement l'annulation tardive de votre trajet. Cette situation est frustrante et nous en excusons.\n\nLe conducteur sera pénalisé dans notre système. Nous vous offrons {COMPENSATION} et vous aidons à trouver une alternative rapidement.\n\nNos excuses pour ce contretemps.\n\nL'équipe Support",
                
                "M./Mme {NOM},\n\nL'annulation à la dernière minute que vous avez subie est inacceptable. Nous comprenons votre déception.\n\nActions :\n• Pénalité appliquée au conducteur\n• Recherche d'alternative prioritaire\n• Compensation de {COMPENSATION}\n\nNous améliorons nos systèmes pour réduire ces cas.\n\nCordialement,\nSmartRide"
            ],
            
            'Problème de paiement' => [
                "{PRENOM}, bonjour,\n\nNous avons identifié l'anomalie de paiement que vous signalez. Nos équipes techniques travaillent à la résolution.\n\nLe problème sera corrigé sous {DELAI}. Si un double prélèvement est confirmé, le remboursement sera automatique.\n\nVeuillez nous excuser pour ce désagrément technique.\n\nService Financier SmartRide",
                
                "Cher(e) client(e),\n\nMerci de nous avoir alertés sur ce problème de transaction. Notre service financier examine immédiatement la situation.\n\nProcessus :\n1. Vérification des transactions\n2. Correction si erreur confirmée\n3. Notification sous {DELAI}\n\nNous restons à votre disposition.\n\nCordialement,\nSupport Paiement"
            ],
            
            default => [
                "Bonjour {PRENOM} {NOM},\n\nNous avons bien reçu votre réclamation et vous remercions de nous l'avoir signalée.\n\nNotre équipe examine votre cas avec attention et vous apportera une réponse personnalisée dans les meilleurs délais.\n\nNous vous contacterons si nous avons besoin d'informations complémentaires.\n\nCordialement,\nLe Service Client SmartRide",
                
                "{PRENOM}, bonjour,\n\nVotre message a bien été pris en compte. Nous traitons chaque réclamation avec le plus grand soin.\n\nUn membre de {CONTACT} étudiera votre situation et vous répondra sous {DELAI}.\n\nMerci de votre confiance.\n\nBien à vous,\nL'équipe SmartRide"
            ]
        };
    }

    private function formaterReponse(string $reponse, Reclamation $reclamation): string
    {
        // Nettoyage et formatage
        $reponse = trim($reponse);
        
        // S'assurer que la réponse commence par une salutation
        $salutations = ['Cher', 'Bonjour', 'Madame', 'Monsieur', 'Cher client', 'Bonjour M.', 'Bonjour Mme'];
        $commenceParSalutation = false;
        
        foreach ($salutations as $salutation) {
            if (stripos($reponse, $salutation) === 0) {
                $commenceParSalutation = true;
                break;
            }
        }
        
        if (!$commenceParSalutation) {
            $reponse = "Bonjour " . $reclamation->getPrenom() . ",\n\n" . $reponse;
        }
        
        // S'assurer qu'il y a une formule de politesse
        if (!preg_match('/(Cordialement|Sincèrement|Bien à vous|Respectueusement)/i', $reponse)) {
            $reponse .= "\n\nCordialement,\nLe Service Client SmartRide";
        }
        
        return $reponse;
    }
}