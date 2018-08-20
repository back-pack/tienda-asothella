<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Repository\ProductRepository;
use App\Form\RequirementType;
use App\Form\ProductRequestType;
use App\Form\CompanyType;
use App\Entity\Requirement;
use App\Entity\ProductRequest;
use App\Entity\Company;
use App\Entity\Product;
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

            // $message = (new \Swift_Message('Bienvenido a Asothella'))
            // ->setFrom('info@asothella.com')
            // ->setTo($company->getEmail())
            // ->setBody(
            //     $this->renderView(
            //         'emails\registration.html.twig',
            //         ['contactName' => $company->getContactName()]
            //     ), 'text/html'
            // );

            // $mailer->send($message);

            return $this->redirectToRoute('client_login', [
            ]);
        }
        return $this->render('client/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
    /**
     * @Route("/login", name="client_login")
     */
    public function login(Request $request, AuthenticationUtils $authenticationUtils)
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
     * @Route("/client", name="client_index")
     */
    public function client()
    {
        $user = $this->getUser();
        //TODO SORTING ASCENDENT
        $requirements = $this->getDoctrine()->getRepository(Requirement::class)->findBy(['company' => $user->getId()]);
        
        return $this->render('client/index.html.twig', [
            'user' => $user->getContactName(),
            'requirements' => $requirements
        ]);
    }

    /**
     * @Route("/client/shopping", name="client_shopping")
     */
    public function shopping(ProductRepository $productRepository, Session $cart)
    {
        if(null === $cart->getId()) {
            $cart = new Session();
            $cart->start();
            $cart->setId(uniqid());
        }

        $items = $cart->get('items');

        return $this->render('shopping/index.html.twig', [
            'products' => $productRepository->findAll(), 
            'items' => $items
            ]);
    }

    /**
     * @Route("/client/shopping/additem/{uid}", name="client_shopping_additem")
     */
    public function addItem(Request $request, $uid, Session $cart)
    {
        if(null === ($cart->getId())) {
            return $this->redirectToRoute('client_shopping');
        }
        $product = $this->getDoctrine()->getRepository(Product::class)->findOneBy(['uid' => $uid]);
        if(!$product) {
            $this->addFlash('danger', 'El producto no existe en la tienda.');
            return $this->redirectToRoute('client_shopping');
        }
        $productRequest = new ProductRequest();
        $form = $this->createForm(ProductRequestType::class, $productRequest);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $productRequest->setProduct($product);
            
            if(null !== $cart->get('items')) {
                foreach($cart->get('items') as $item) {
                    $cartProducts[md5(uniqid())] = $item;
                }
            }
            
            $cartProducts[md5(uniqid())] = $productRequest;
            $cart->set('items', $cartProducts);
            
            $this->addFlash('success', 'El producto fue agregado al carrito.');
            return $this->redirectToRoute('client_shopping');
        }
        return $this->render('shopping/addItem.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    /**
     * @Route("/client/shopping/edititem/{itemId}", name="client_shopping_edititem")
     */
    public function edititem(Session $cart, Request $request, $itemId)
    {
        if(null === ($cart->getId())) {
            return $this->redirectToRoute('client_shopping');
        }
        $cartProducts = $cart->get('items');
        $productReq = $cartProducts[$itemId];

        $form = $this->createForm(ProductRequestType::class, $productReq);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $cartProducts[$itemId] = $productReq;
            $cart->set('items', $cartProducts);

            $this->addFlash('success', 'El producto fue modificado.');
            
            return $this->redirectToRoute('client_shopping');
        }
        return $this->render('shopping/addItem.html.twig', [
            'form' => $form->createView(),
            'edit' => true,
            'product' => $productReq->getProduct(),
        ]);
    }

    /**
     * @Route("/client/shopping/removeitem/{itemId}", name="client_shopping_removeitem")
     */
    public function removeitem(Session $cart, $itemId)
    {
        if(null === ($cart->getId())) {
            return $this->redirectToRoute('client_shopping');
        }
        $cartProducts = $cart->get('items');
        unset($cartProducts[$itemId]);

        $cart->set('items', $cartProducts);

        if(empty($cartProducts)) {
            $cart->invalidate();
            $this->addFlash('success', 'El carrito fue vaciado');
        } else {
            $this->addFlash('success', 'El producto fue removido.');
        }
        return $this->redirectToRoute('client_shopping');
    }

    /**
     * @Route("/client/shopping/dropcart", name="client_shopping_dropcart")
     */
    public function dropCart(Session $cart)
    {
        if(null === ($cart->getId())) {
            return $this->redirectToRoute('client_shopping');
        }
        $cart->invalidate();

        return $this->redirectToRoute('client_shopping');
        
    }

    /**
     * @Route("/client/requirement/new", name="client_requirement_new")
     */
    public function newRequirement(Session $cart)
    {
        if(null === ($cart->getId())) {
            return $this->redirectToRoute('client_shopping');
        }
        
        $cartItems = $cart->get('items');
        $em = $this->getDoctrine()->getManager();
        $products = $em->getRepository(Product::class)->findAll();
        
        $requirement = new Requirement();
        $finalCost = null;
        foreach($cartItems as $item) {
            $index = array_search($item->getProduct(), $products);
            $item->setProduct($products[$index]);
            
            $finalCost += $item->getProduct()->getPrice() * $item->getQuantity();
            $requirement->addProductRequest($item);
            $em->persist($item);
        }

        $requirement
            ->setFinalCost($finalCost)
            ->setCreationDate(new \DateTime('today'))
            ->setRequirementNumber(md5(uniqid()))
            ->setStatus(Constant::TO_BE_APPROVED)
            ->setCompany($this->getUser())
            ;
        
        $em->persist($requirement);
        $em->flush();

        $cart->invalidate();

        $this->addFlash('success', 'Su solicitud es la numero: RQ'.$requirement->getId().'. En breve procesaremos su solicitud!');

        return $this->redirectToRoute('client_index');
    }

    /**
     * @Route("/client/requirement/edit", name="client_requirement_edit")
     */
    public function editRequirement($reqId)
    {
        //Terminar esta parte
        $em = $this->getDoctrine()->getManager();
        $requirement = $em->getRepository(Requirement::class)->findOneBy(['requirementNumber' => $reqId]);

        if(!$requirement) {
            $this->addFlash('danger', 'El requerimiento ya no existe');
            return $this->redirectToRoute('client_index');
        }
        
        $cart = new Session();
        $cart->setId($requirement->getId());
        $cart->set('items', $requirement->getProductRequest());
        return $this->render('admin/shopping/viewCart.html.twig', [
            'cartProducts' => $cartProducts,
            'company' => $company->getName()
        ]);
        


    }
}
