<?php

namespace App\Controller;

use DateTime;
use DateTimeImmutable;
use App\Entity\Incident;
use App\Form\IncidentType;
use App\Message\MailNotification;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    /**
     * @Route("/home", name="app_home")
     */
    public function index(Request $request, EntityManagerInterface $em, MessageBusInterface $bus): Response
    {
        $task = new Incident();
        $task->setUser($this->getUser())
            ->setCreatedAt(new DateTimeImmutable('now'));

        $form = $this->createForm(IncidentType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task = $form->getData();

            $em->persist($task);
            $em->flush();

            $bus->dispatch(new MailNotification($task->getDescription(), $task->getId(), $task->getUser()->getEmail()));

            return $this->redirectToRoute('app_home');
        }

        return $this->render('home/index.html.twig', [
            'form' => $form->createView(),
            'controller_name' => 'HomeController',
        ]);
    }
}
