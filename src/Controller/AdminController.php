<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use App\Form\UserType;

class AdminController extends Controller
{
    /**
     * @Route("/admin/login", name="admin_login")
     */
    public function login(Request $request, AuthenticationUtils $authenticationUtils)
    {   
        var_dump($this->getUser());
        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();
    
        return $this->render('admin/login.html.twig', [
            'controller_name' => 'AdminController',
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }
    /**
     * @Route("/admin/logout", name="admin_logout")
     */
    public function logout()
    {   
        var_dump($this->getUser());
        return $this->redirectToRoute('admin_login', []);
    }

    /**
     * @Route("/admin", name="admin")
     */
    public function index()
    {    
        return $this->render('admin/index.html.twig', [
        ]);
    }

    /**
     * @Route("/admin/new/user", name="admin_new_user")
     */
    public function newUser(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
            $user
                ->setIsActive(true)
                ->setCreationDate(new \DateTime('today'))
                ->setPassword($password);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('admin', [
            ]);
        }

        return $this->render('admin/newUser.html.twig', [
            'form' => $form->createView()
        ]);
    }

}
