<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\Security;
use ReCaptcha\ReCaptcha;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token');
       
        // On mémorise le dernier username/email saisi
        $request->getSession()->set(Security::LAST_USERNAME, $email);

        return new Passport(
            new \Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }
    

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        // Redirection vers l’URL demandée avant login
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $user = $token->getUser();

        // Vérifie si l'utilisateur a une méthode getType() et récupère la valeur enum
        if (method_exists($user, 'getType')) {
            $type = $user->getType(); // Type enum

            if ($type?->value === 'Client') {
                // Redirection vers la page edit du client
                return new RedirectResponse($this->urlGenerator->generate('app_user_edit', [
                    'id' => $user->getId(),
                ]));
            } elseif ($type?->value === 'Admin') {
                // Redirection vers le dashboard (liste des users)
                return new RedirectResponse($this->urlGenerator->generate('app_user_index'));
            }
        }

        // Par défaut, redirection vers login
        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
