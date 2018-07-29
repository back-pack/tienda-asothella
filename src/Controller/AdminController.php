<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use App\Entity\User;
use App\Form\UserType;
use App\Entity\Requirement;
use App\Form\RequirementType;
use App\Entity\ProductRequest;
use App\Entity\RoofTile;
use App\Helper\Constant;

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

    /**
     * @Route("/admin/new/requirement", name="admin_new_requirement")
     * @Route("/superadmin/new/requirement", name="superadmin_new_requirement")
     */
    public function newRequirement(Request $request, AuthorizationCheckerInterface $authChecker)
    {
        $productRequest = new ProductRequest();
        $requirement = new Requirement();
        $requirement->addProductRequest($productRequest);
        $form = $this->createForm(RequirementType::class, $requirement, ['company' => true]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $em = $this->getDoctrine()->getManager();
            $finalCost = null;
            foreach($requirement->getProductRequests() as $product)
            {
                $tile = $em->getRepository(RoofTile::class)->findOneBy(['type' => $product->getType()]);
                if(!$tile)
                {
                    $this->addFlash('danger', 'Un error ocurrió con el envío del formulario.');
                    return $this->redirectToRoute('request');
                }
                $cost = $tile->getCost() * $product->getQuantity();
                $product
                    ->setCost($cost);
                if(is_null($product->getRequirement()))
                {
                    $product->setRequirement($requirement);
                }
                $finalCost += $cost;
            }
            
            $requirement
                ->setFinalCost($finalCost)
                ->setCreationDate(new \DateTime('today'))
                ->setRequirementNumber(uniqid())
                ->setStatus(Constant::TO_BE_PROCESSED)
                ;
            
            $em->persist($requirement);
            $em->flush();

            $this->addFlash('success', 'Su solicitud es la numero: RQ'.$requirement->getId().'. En breve procesaremos su solicitud!');

            if ($authChecker->isGranted('ROLE_SUPERADMIN')) {
                return $this->redirectToRoute('superadmin_index');
            }
            return $this->redirectToRoute('admin_index');
        }
        return $this->render('client/newRequirement.html.twig', [
            'form' => $form->createView(),
            // 'prices' => $prices
        ]);
    }
}
