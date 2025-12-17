<?php

namespace App\Tests\Controller;

use App\Entity\Reclamation;
use App\Entity\User;
use App\Enum\TypeReclamation;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReclamationControllerTest extends WebTestCase
{
    public function testNewReclamationRedirectsAnonymous()
    {
        $client = static::createClient();
        $client->request('GET', '/reclamation/new');

        $this->assertResponseRedirects('/login');
    }

    public function testIndexRedirectsAnonymous()
    {
        $client = static::createClient();
        $client->request('GET', '/reclamation'); // Assuming /reclamation is the index route path

        $this->assertResponseRedirects('/login');
    }

    public function testNewReclamationSubmitSuccess()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // Retrieve the test user
        $testUser = $userRepository->findOneBy(['email' => 'test@example.com']);
        
        // Create user if not exists (mocking logic for test environment)
        if (!$testUser) {
             // In a real test environment, we would create a user here or load fixtures
             $this->markTestSkipped('Test user not found. Please load fixtures.');
        }

        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/reclamation/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Ajouter')->form();
        $form['reclamation[type_reclamation]'] = TypeReclamation::RETARD->value;
        $form['reclamation[message]'] = 'Le chauffeur était en retard de 15 minutes.';
        $form['reclamation[date_reclamation]'] = (new \DateTime())->format('Y-m-d');

        $client->submit($form);

        $this->assertResponseRedirects('/reclamation/');
        $client->followRedirect();
        $this->assertSelectorExists('.alert-success');
    }
}
