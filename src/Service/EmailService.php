<?php

namespace App\Service;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

readonly class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment     $twig
    )
    {
    }

    /**
     * @throws SyntaxError
     * @throws TransportExceptionInterface
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function sendContactEmail(string $to, string $subject, array $data): void
    {
        // Rendu du template d'email avec les données du formulaire
        $emailContent = $this->twig->render('emails/contact_email.html.twig', [
            'name' => $data['name'],
            'surname' => $data['surname'],
            'email' => $data['email'],
            'department' => $data['department'],
            'message' => $data['message'],
        ]);

        // Création et envoi de l'email
        $email = (new Email())
            ->from('contact@shinkyokai.com')
            ->to($to)
            ->subject($subject)
            ->html($emailContent);

        $this->mailer->send($email);
    }
}
