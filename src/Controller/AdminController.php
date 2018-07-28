<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use App\Form\UserType;
use App\Entity\Requirement;

class AdminController extends Controller
{
    /**
     * @Route("/admin/login", name="admin_login")
     */
    public function login(Request $request, AuthenticationUtils $authenticationUtils)
    {   
        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();
    
        return $this->render('admin/login.html.twig', [
            'controller_name' => 'AdminController',
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @Route("/admin", name="admin_index")
     * @Route("/superadmin", name="superadmin_index")
     */
    public function index()
    {   
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');
        $requirements = $this->getDoctrine()->getRepository(Requirement::class)->findAll();
        return $this->render('admin/index.html.twig', [
            'requirements' => $requirements
        ]);
    }

    /**
     * @Route("/superadmin/new/user", name="superadmin_new_user")
     */
    public function newUser(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->denyAccessUnlessGranted('ROLE_SUPERADMIN', null, 'Unable to access this page!');
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

            $this->addFlash('success', 'Usuario creado!');
            return $this->redirectToRoute('superadmin_index', [
            ]);
        }

        return $this->render('admin/newUser.html.twig', [
            'form' => $form->createView()
        ]);
    }

}
