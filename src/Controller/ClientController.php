<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Repository\RequirementRepository;
use App\Repository\ProductRepository;
use App\Repository\CompanyRepository;
use App\Form\RequirementType;
use App\Form\ProductRequestType;
use App\Form\CompanyType;
use App\Entity\Requirement;
use App\Entity\ProductRequest;
use App\Entity\Company;
use App\Entity\Product;
use App\Helper\Status;
use Doctrine\Common\Collections\ArrayCollection;
use App\Models\Messenger;
use App\NotificationTypes\RegistrationNotification;
use App\NotificationPlatforms\EmailNotificationPlatform;

class ClientController extends Controller
{
    /**
     * @Route("/register", name="register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, CompanyRepository $companyRepository)
    {
        $company = new Company();
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $companyRepository->save($company, $passwordEncoder);

            // $messenger = new Messenger([new EmailNotificationPlatform()]);
            // $messenger->send(new RegistrationNotification(), [
            //     'to' => $company->getEmail(),
            //     'data' => ['contactName' => $company->getContactName()]
            // ]);

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
        $requirements = $this->getDoctrine()->getRepository(Requirement::class)->findBy(['company' => $user->getId()], ['id' => 'DESC']);
        
        return $this->render('client/index.html.twig', [
            'user' => $user->getContactName(),
            'requirements' => $requirements
        ]);
    }

    /**
     * @Route("/client/shopping", name="client_shopping")
     */
    public function shopping(ProductRepository $productRepository, Session $cart, Request $request)
    {
        if(null === $cart->get('cart')) {
            $cart->set('cart', md5(uniqid()));
        }
        $cart->set('edit', null);
        $items = $cart->get('items');
        $products = $productRepository->findAll();
        $requirement = new Requirement();
        $requirement->setCompany($this->getUser());
        $form = $this->createForm(RequirementType::class, $requirement);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            
            $em = $this->getDoctrine()->getManager();
            
            $finalCost = null;
            foreach($items as $item) {
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
                ->setStatus(Status::TO_BE_APPROVED)
                ->setCompany($this->getUser());
                ;
            
            $em->persist($requirement);
            $em->flush();

            $cart->invalidate();

            $this->addFlash('success', 'Su solicitud es la numero: RQ'.$requirement->getId().'. En breve procesaremos su solicitud!');

            return $this->redirectToRoute('client_index');
        }

        return $this->render('shopping/index.html.twig', [
            'products' => $products, 
            'items' => $items,
            'form' => $form->createView()]);
    }

    /**
     * @Route("/client/shopping/additem/{uid}", name="client_shopping_additem")
     */
    public function addItem(Request $request, $uid, Session $cart)
    {
        if(null === $cart->get('cart')) {
            return $this->redirectToRoute('client_index');
        }
        $product = $this->getDoctrine()->getRepository(Product::class)->findOneBy(['uid' => $uid]);
        if(!$product) {
            $this->addFlash('danger', 'El producto no existe en la tienda.');
            if($cart->get('edit') === true) {
                return $this->redirectToRoute('client_requirement_edit', ['reqId' => $cart->get('cart')]);
            } 
            return $this->redirectToRoute('client_shopping');
        }
        $productRequest = new ProductRequest();
        $form = $this->createForm(ProductRequestType::class, $productRequest);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $productRequest->setProduct($product);
            
            if(null === $cart->get('edit')) {
                if(null !== $cart->get('items')) {
                    foreach($cart->get('items') as $item) {
                        $cartProducts[md5(uniqid())] = $item;
                    }
                }
            } else {
                $cartProducts = $cart->get('items');
            }
            
            $cartProducts[md5(uniqid())] = $productRequest;
            $cart->set('items', $cartProducts);
            
            $this->addFlash('success', 'El producto fue agregado al carrito.');

            if($cart->get('edit') === true) {
                return $this->redirectToRoute('client_requirement_edit', ['reqId' => $cart->get('cart')]);
            } 
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
            return $this->redirectToRoute('client_index');
        }
        $cart->invalidate();

        return $this->redirectToRoute('client_index');
        
    }

    /**
     * @Route("/client/requirement/new", name="client_requirement_new")
     */
    public function newRequirement(Session $cart)
    {
        if(null === ($cart->get('cart'))) {
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
            ->setStatus(Status::TO_BE_APPROVED)
            ->setCompany($this->getUser())
            ;
        
        $em->persist($requirement);
        $em->flush();

        $cart->invalidate();

        $this->addFlash('success', 'Su solicitud es la numero: RQ'.$requirement->getId().'. En breve procesaremos su solicitud!');

        return $this->redirectToRoute('client_index');
    }

    /**
     * @Route("/client/requirement/edit/{reqId}", name="client_requirement_edit")
     */
    public function editRequirement($reqId, Request $request, RequirementRepository $requirementRepository, Session $cart, ProductRepository $productRepository)
    {
        $requirement = $requirementRepository->findOneBy(['requirementNumber' => $reqId]);
        if(!$requirement) {
            return $this->redirectToRoute('client_index');
        }

        if($cart->get('cart') !== $requirement->getRequirementNumber()) {
            $cart->set('items', null);
        }

        $items = $requirement->getProductRequests();

        if(null === $cart->get('items')) {
            $cart->set('cart', $requirement->getRequirementNumber());
            $cart->set('items', $items);
        } else {
            $items = $cart->get('items');
        }
        if(null === $cart->get('edit')) {
            $cart->set('edit', true);
        }
        $products = $productRepository->findAll();

        $form = $this->createForm(RequirementType::class, $requirement);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $finalCost = 0;
            foreach($items as $cp) {
                $finalCost += $cp->getProduct()->getPrice() * $cp->getQuantity();
            }

            $originalProducts = new ArrayCollection();
            $editProducts = new ArrayCollection();

            foreach($requirement->getProductRequests() as $pr) {
                $originalProducts->add($pr);
            }
            foreach($items as $item) {
                $editProducts->add($item);
            }
            $productRequestsToAdd = array_udiff($editProducts->toArray(), $originalProducts->toArray(),
                function ($obj_a, $obj_b) {
                    return $obj_a->getId() - $obj_b->getId();
                }
            );
            foreach($productRequestsToAdd as $pr) {
                $index = array_search($cp->getProduct(), $products);
                $pr->setProduct($products[$index]);
                $requirement->addProductRequest($pr);
                $em->persist($pr);
            }

            $productRequestsToRemove = array_udiff($originalProducts->toArray(), $editProducts->toArray(),
            function ($obj_a, $obj_b) {
                    return $obj_a->getId() - $obj_b->getId();
                }
            );

            foreach($productRequestsToRemove as $pr) {
                $requirement->removeProductRequest($pr);
            }

            $requirement->setFinalCost($finalCost);
            $em->persist($requirement);
            $em->flush();
            $cart->invalidate();
            $this->addFlash('success', 'Su solicitud ha sido actualizada.');

            return $this->redirectToRoute('client_index');
        }
        
        return $this->render('shopping/index.html.twig', [
            'form' => $form->createView(),
            'items' => $items,
            'products' => $products, 
            'id' => $requirement->getId()
            ]);

    }

    /**
     * @Route("/client/requirement/delete/{reqId}", name="client_requirement_delete")
     */
    public function deleteRequirement($reqId)
    {
        $em = $this->getDoctrine()->getManager();
        $requirement = $em->getRepository(Requirement::class)->findOneBy(['requirementNumber' => $reqId]);
        if (!$requirement) {
            throw $this->createNotFoundException('No existe tal requerimiento: '.$reqId);
        }
        $em->remove($requirement);
        $em->flush();

        return $this->redirectToRoute('client_index');
    }

     /**
     * @Route("/client/user/edit", name="client_user_edit")
     */
    public function editUser(Request $request, UserPasswordEncoderInterface $passwordEncoder) 
    {   
        $em = $this->getDoctrine()->getManager();     
        $user = $em->getRepository(Company::class)->find($this->getUser()->getId());
        if(!$user) {
            $this->addFlash('danger', 'No existe tal usuario.');
        }
        $dbPassword = $user->getPassword();

        $form = $this->createForm(CompanyType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            $userExists = $em->getRepository(Company::class)-findOneBy(['email' => $user->getEmail(), 'name' => $user->getName()]);
            if($userExists) {
                $this->addFlash('success', 'El usuario ya existe.');
                return $this->redirectToRoute('client_user_edit');
            }

            if($user->getPlainPassword() !== "****") {
                $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($password);
            } else {
                $user->setPassword($dbPassword);
            }
            
            $em->flush();

            $this->addFlash('success', 'Usuario actualizado!');
            return $this->redirectToRoute('client_index');
            
        }
        return $this->render('client/editUser.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
