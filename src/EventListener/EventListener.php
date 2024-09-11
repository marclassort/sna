<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ExceptionListener
{
    private $mailer;
    private $twig;

    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    /**
     * @throws SyntaxError
     * @throws TransportExceptionInterface
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($event->getResponse()->getStatusCode() === Response::HTTP_INTERNAL_SERVER_ERROR) {
            $email = (new Email())
                ->from('no-reply@shinkyokai.com')
                ->to('marc.lassort@gmail.com')
                ->subject('Erreur 500 détectée')
                ->html($this->twig->render('emails/error500.html.twig', [
                    'exception' => $exception,
                ]));

            $this->mailer->send($email);
        }
    }
}
