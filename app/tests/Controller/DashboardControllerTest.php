<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    private function authenticateClient(): KernelBrowser
    {
        $client = static::createClient();

        // Simuler une authentification
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser = $userRepository->findOneBy(['username' => 'admin']);

        if (! $testUser) {
            $this->markTestSkipped('Utilisateur admin non trouvé en base de données');
        }

        $client->loginUser($testUser);

        return $client;
    }

    public function testDashboardRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseRedirects('/login');
    }

    public function testDashboardPageIsAccessibleWhenAuthenticated(): void
    {
        $client = $this->authenticateClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Tableau de bord');
    }

    public function testDashboardDisplaysStatistics(): void
    {
        $client = $this->authenticateClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        // Vérifier la présence des cartes de statistiques
        $this->assertGreaterThan(0, $crawler->filter('.card.glass-card')->count());
    }

    public function testDashboardDisplaysOperationsTable(): void
    {
        $client = $this->authenticateClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        // Vérifier la présence de tables
        $this->assertGreaterThan(0, $crawler->filter('table')->count());
    }

    public function testDashboardHasSidebarNavigation(): void
    {
        $client = $this->authenticateClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        // Vérifier la présence de la sidebar
        $this->assertCount(1, $crawler->filter('.app-sidebar'));
        $this->assertCount(1, $crawler->filter('.sidebar-menu'));
    }

    public function testDashboardHasTopbar(): void
    {
        $client = $this->authenticateClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        // Vérifier la présence de la topbar
        $this->assertCount(1, $crawler->filter('.app-topbar'));
        $this->assertSelectorExists('.topbar-user');
    }
}
