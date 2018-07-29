<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\RequirementType;
use App\Form\CompanyType;
use App\Entity\Requirement;
use App\Entity\ProductRequest;
use App\Entity\Company;
use App\Entity\RoofTile;
use App\Helper\Constant;

class ClientController extends Controller
{
    /**
     * @Route("/register", name="register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, \Swift_Mailer $mailer)
    {
        $company = new Company();
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $company
                ->setCreationDate(new \DateTime('today'))
                ->setStatus(Constant::PENDING_FOR_APPROVAL)
                ->setActive(false);
            $password = $passwordEncoder->encodePassword($company, $company->getPlainPassword());
            $company->setPassword($password);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($company);
            $entityManager->flush();

            $message = (new \Swift_Message('Bienvenido a Asothella'))
            ->setFrom('info@asothella.com')
            ->setTo($company->getEmail())
            ->setBody(
                $this->renderView(
                    'emails\registration.html.twig',
                    ['contactName' => $company->getContactName()]
                ), 'text/html'
            );

            $mailer->send($message);

            return $this->redirectToRoute('login', [
            ]);
        }
        return $this->render('client/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
    /**
     * @Route("/login", name="login")
     */
    public function login(Request $request,AuthenticationUtils $authenticationUtils)
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();
    
        return $this->render('client/login.html.twig', [
            'controller_name' => 'ClientController',
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @Route("/client", name="client")
     */
    public function client()
    {
        $user = $this->getUser();
        $requirements = $this->getDoctrine()->getRepository(Requirement::class)->findBy(['company' => $this->getUser()->getId()]);
        
        return $this->render('client/index.html.twig', [
            'user' => $user->getContactName(),
            'requirements' => $requirements
        ]);
    }

    /**
     * @Route("/client/requirement", name="requirement")
     */
    public function newRequirement(Request $request)
    {
        $productRequest = new ProductRequest();
        $requirement = new Requirement();
        $requirement->addProductRequest($productRequest);
        $form = $this->createForm(RequirementType::class, $requirement);
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
                ->setCompany($this->getUser())
                ->setRequirementNumber(uniqid())
                ->setStatus(Constant::TO_BE_APPROVED)
                ;
            
            $em->persist($requirement);
            $em->flush();

            $this->addFlash('success', 'Su solicitud es la numero: RQ'.$requirement->getId().'. En breve procesaremos su solicitud!');

            return $this->redirectToRoute('client');
        }
        return $this->render('client/newRequirement.html.twig', [
            'form' => $form->createView(),
            // 'prices' => $prices
        ]);
    }
}
