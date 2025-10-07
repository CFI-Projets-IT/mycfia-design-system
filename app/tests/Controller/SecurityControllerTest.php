<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Connexion');
    }

    public function testLoginFormIsPresent(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        // Vérifier la présence du formulaire
        $this->assertCount(1, $crawler->filter('form'));
        $this->assertCount(1, $crawler->filter('input[name="_username"]'));
        $this->assertCount(1, $crawler->filter('input[name="_password"]'));
        $this->assertCount(1, $crawler->filter('input[name="_csrf_token"]'));
        $this->assertCount(1, $crawler->filter('button[type="submit"]'));
    }

    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'admin',
            '_password' => 'admin123',
        ]);

        $client->submit($form);

        // Vérifier la redirection après connexion réussie
        $this->assertResponseRedirects();
        $client->followRedirect();

        // On doit arriver sur le dashboard
        $this->assertRouteSame('app_dashboard');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'invalid_user',
            '_password' => 'wrong_password',
        ]);

        $client->submit($form);

        // Vérifier la redirection vers login
        $this->assertResponseRedirects('/login');
        $client->followRedirect();

        // Vérifier la présence d'un message d'erreur
        $this->assertSelectorExists('.alert-danger');
    }

    public function testLogout(): void
    {
        $client = static::createClient();

        // Se connecter d'abord
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'admin',
            '_password' => 'admin123',
        ]);
        $client->submit($form);
        $client->followRedirect();

        // Se déconnecter
        $client->request('GET', '/logout');

        // Vérifier la redirection vers login
        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertRouteSame('app_login');
    }

    public function testAccessDashboardWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        // Doit être redirigé vers login
        $this->assertResponseRedirects();
        $this->assertResponseHeaderSame('location', '/login');
    }
}
