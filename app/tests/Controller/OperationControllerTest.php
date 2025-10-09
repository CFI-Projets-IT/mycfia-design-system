<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OperationControllerTest extends WebTestCase
{
    private function authenticateClient(): KernelBrowser
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser = $userRepository->findOneBy(['username' => 'admin']);

        if (! $testUser) {
            $this->markTestSkipped('Utilisateur admin non trouvé en base de données');
        }

        $client->loginUser($testUser);

        return $client;
    }

    public function testOperationIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/operations');

        $this->assertResponseRedirects('/login');
    }

    public function testOperationIndexIsAccessibleWhenAuthenticated(): void
    {
        $client = $this->authenticateClient();
        $client->request('GET', '/operations');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Opérations Marketing');
    }

    public function testOperationIndexDisplaysTable(): void
    {
        $client = $this->authenticateClient();
        $crawler = $client->request('GET', '/operations');

        $this->assertResponseIsSuccessful();

        // Vérifier la présence de la table
        $this->assertCount(1, $crawler->filter('table'));
        $this->assertSelectorExists('thead');
        $this->assertSelectorExists('tbody');
    }

    public function testOperationIndexHasSearchInput(): void
    {
        $client = $this->authenticateClient();
        $crawler = $client->request('GET', '/operations');

        $this->assertResponseIsSuccessful();

        // Vérifier la présence du champ de recherche
        $this->assertCount(1, $crawler->filter('input[placeholder*="Rechercher"]'));
    }

    public function testOperationIndexHasNewButton(): void
    {
        $client = $this->authenticateClient();
        $crawler = $client->request('GET', '/operations');

        $this->assertResponseIsSuccessful();

        // Vérifier la présence du bouton "Nouvelle opération"
        $this->assertGreaterThan(0, $crawler->filter('a[href*="/operations/new"]')->count());
    }

    public function testOperationIndexDisplaysDataTableController(): void
    {
        $client = $this->authenticateClient();
        $crawler = $client->request('GET', '/operations');

        $this->assertResponseIsSuccessful();

        // Vérifier la présence du contrôleur Stimulus DataTable
        $this->assertCount(1, $crawler->filter('[data-controller="datatable"]'));
    }

    public function testOperationNewPageIsAccessible(): void
    {
        $client = $this->authenticateClient();
        $client->request('GET', '/operations/new');

        $this->assertResponseIsSuccessful();
    }
}
