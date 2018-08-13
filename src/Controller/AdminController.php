<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Entity\User;
use App\Entity\Requirement;
use App\Entity\ProductRequest;
use App\Entity\Company;
use App\Entity\Product;
use App\Form\ProductType;
use App\Form\UserType;
use App\Form\ProductRequestType;
use App\Repository\ProductRepository;
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
     * @Route("/superadmin/user/list", name="superadmin_user_list")
     */
    public function listUser()
    {
        $this->denyAccessUnlessGranted('ROLE_SUPERADMIN', null, 'Unable to access this page!');
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();
        return $this->render('admin/listUser.html.twig', [
            'users' => $users
        ]);
    }

    /**
     * @Route("/superadmin/user/new", name="superadmin_user_new")
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
     * @Route("/superadmin/user/edit/{userId}", name="superadmin_user_edit")
     * @Route("/admin/user/edit/{userId}", name="admin_user_edit")
     */
    public function editUser(Request $request, $userId, UserPasswordEncoderInterface $passwordEncoder, AuthorizationCheckerInterface $authChecker) 
    {   
        $em = $this->getDoctrine()->getManager();     
        if($authChecker->isGranted('ROLE_SUPERADMIN')) {
            $user = $em->getRepository(User::class)->find($userId);    
        } else {
            $user = $em->getRepository(User::class)->find($this->getUser()->getId());
        }
        if(!$user) {
            throw $this->createNotFoundException('No existe tal usuario: '.$userId);
        }
        $dbPassword = $user->getPassword();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            if($user->getPlainPassword() !== "****") {
                $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($password);
            } else {
                $user->setPassword($dbPassword);
            }
            $em->flush();

            if ($authChecker->isGranted('ROLE_SUPERADMIN')) {
                $redirectToIndex = $this->redirectToRoute('superadmin_user_list');
            } else {
                $redirectToIndex = $this->redirectToRoute('admin_index');
            }
            $this->addFlash('success', 'Usuario actualizado!');
            return $redirectToIndex;
            
        }
        return $this->render('admin/newUser.html.twig', [
            'form' => $form->createView(),
            'edit' => true
        ]);
    }

    /**
     * @Route("/superadmin/user/delete/{userId}", name="superadmin_user_delete")
     */
    public function deleteUser($userId) 
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->find($userId);
        if (!$user) {
            $this->addFlash('danger', 'El usuario no existe.');
            return $this->redirectToRoute('superadmin_user_list', [
            ]);
        }
        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('superadmin_user_list');
    }

    /**
     * @Route("/admin/requirement/new", name="admin_requirement_new")
     * @Route("/superadmin/requirement/new", name="superadmin_requirement_new")
     */
    public function newRequirement(Session $cart, AuthorizationCheckerInterface $authChecker)
    {
        if(null === ($cart->getId())) {
            if($authChecker->isGranted('ROLE_SUPERADMIN')) {
                return $this->redirectToRoute('superadmin_shopping');
            } else {
                return $this->redirectToRoute('admin_shopping');
            }
        }
        
        $cartItems = $cart->get('items');
        $em = $this->getDoctrine()->getManager();

        $requirement = new Requirement();
        
        $finalCost = null;
        foreach($cartItems as $item) {
            $finalCost += $item->getProduct()->getPrice() * $item->getQuantity();
            $requirement->addProductRequest($item);
        }

        $company = $em->getRepository(Company::class)->find(1);

        $requirement
            ->setFinalCost($finalCost)
            ->setCreationDate(new \DateTime('today'))
            ->setRequirementNumber(md5(uniqid()))
            ->setStatus(Constant::TO_BE_APPROVED)
            //TODO
            ->setCompany($company)
            ;
        
        $em->persist($requirement);
        $em->flush();

        $cart->invalidate();

        $this->addFlash('success', 'Su solicitud es la numero: RQ'.$requirement->getId().'. En breve procesaremos su solicitud!');

        if ($authChecker->isGranted('ROLE_SUPERADMIN')) {
            return $this->redirectToRoute('superadmin_index');
        }
        return $this->redirectToRoute('admin_index');
    }

    /**
     * @Route("/admin/shopping/additem/{itemId}", name="admin_shopping_additem")
     * @Route("/superadmin/shopping/additem/{itemId}", name="superadmin_shopping_additem")
     */
    public function addItem(Request $request, $itemId, Session $cart, AuthorizationCheckerInterface $authChecker)
    {
        if(null === ($cart->getId())) {
            if($authChecker->isGranted('ROLE_SUPERADMIN')) {
                return $this->redirectToRoute('superadmin_shopping');
            } else {
                return $this->redirectToRoute('admin_shopping');
            }
        }
        $product = $this->getDoctrine()->getRepository(Product::class)->findOneBy(['uid' => $itemId]);
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
            
            if ($authChecker->isGranted('ROLE_SUPERADMIN')) {
                return $this->redirectToRoute('superadmin_shopping');
            }
            return $this->redirectToRoute('admin_shopping');

        }
        return $this->render('admin/shopping/addItem.html.twig', [
            'form' => $form->createView(),
            'product' => $product
        ]);
    }

    /**
     * @Route("/admin/shopping/viewcart", name="admin_shopping_viewcart")
     * @Route("/superadmin/shopping/viewcart", name="superadmin_shopping_viewcart")
     */
    public function viewCart(Request $request, Session $cart, AuthorizationCheckerInterface $authChecker)
    {
        if(null === ($cart->getId())) {
            if($authChecker->isGranted('ROLE_SUPERADMIN')) {
                return $this->redirectToRoute('superadmin_shopping');
            } else {
                return $this->redirectToRoute('admin_shopping');
            }
        }
        $cartProducts = $cart->get('items');
        return $this->render('admin/shopping/viewCart.html.twig', [
            'cartProducts' => $cartProducts
        ]);
    }

    /**
     * @Route("/admin/shopping/edititem/{itemId}", name="admin_shopping_edititem")
     * @Route("/superadmin/shopping/edititem/{itemId}", name="superadmin_shopping_edititem")
     */
    public function edititem(Session $cart, Request $request, $itemId, AuthorizationCheckerInterface $authChecker)
    {
        //TODO
        if(null === ($cart->getId())) {
            if($authChecker->isGranted('ROLE_SUPERADMIN')) {
                return $this->redirectToRoute('superadmin_shopping');
            } else {
                return $this->redirectToRoute('admin_shopping');
            }
        }
        $cartProducts = $cart->get('items');
        $productReq = $cartProducts[$itemId];

        $form = $this->createForm(ProductRequestType::class, $productReq);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $cartProducts[$itemId] = $productReq;
            $cart->set('items', $cartProducts);

            $this->addFlash('success', 'El producto fue modificado.');
            
            if ($authChecker->isGranted('ROLE_SUPERADMIN')) {
                return $this->redirectToRoute('superadmin_shopping_viewcart');
            }
            return $this->redirectToRoute('admin_shopping_viewcart');
        }
        return $this->render('admin/shopping/addItem.html.twig', [
            'form' => $form->createView(),
            'edit' => true,
            'product' => $productReq->getProduct(),
        ]);
    }

    /**
     * @Route("/admin/shopping/removeitem/{itemId}", name="admin_shopping_removeitem")
     * @Route("/superadmin/shopping/removeitem/{itemId}", name="superadmin_shopping_removeitem")
     */
    public function removeitem(Session $cart, $itemId, AuthorizationCheckerInterface $authChecker)
    {
        if(null === ($cart->getId())) {
            if($authChecker->isGranted('ROLE_SUPERADMIN')) {
                return $this->redirectToRoute('superadmin_shopping');
            } else {
                return $this->redirectToRoute('admin_shopping');
            }
        }
        $cartProducts = $cart->get('items');
        unset($cartProducts[$itemId]);

        $cart->set('items', $cartProducts);

        if(empty($cartProducts)) {
            $cart->invalidate();

            $this->addFlash('success', 'El carrito fue vaciado');
            
            if($authChecker->isGranted('ROLE_SUPERADMIN')) {
                return $this->redirectToRoute('superadmin_shopping');
            } else {
                return $this->redirectToRoute('admin_shopping');
            }
        }
        $this->addFlash('success', 'El item fue removido del carrito');
        return $this->render('admin/shopping/viewCart.html.twig', [
            'cartProducts' => $cartProducts
        ]);
    }

    /**
     * @Route("/admin/shopping/dropcart", name="admin_shopping_dropcart")
     * @Route("/superadmin/shopping/dropcart", name="superadmin_shopping_dropcart")
     */
    public function dropCart(Session $cart, AuthorizationCheckerInterface $authChecker)
    {
        if(null === ($cart->getId())) {
            if($authChecker->isGranted('ROLE_SUPERADMIN')) {
                return $this->redirectToRoute('superadmin_shopping');
            } else {
                return $this->redirectToRoute('admin_shopping');
            }
        }
        $cart->invalidate();

        $this->addFlash('success', 'El carrito fue vaciado.');

        if($authChecker->isGranted('ROLE_SUPERADMIN')) {
            return $this->redirectToRoute('superadmin_shopping');
        } else {
            return $this->redirectToRoute('admin_shopping');
        }
        
    }

    /**
     * @Route("/admin/shopping", name="admin_shopping")
     * @Route("/superadmin/shopping", name="superadmin_shopping")
     */
    public function shopping(ProductRepository $productRepository, Session $cart)
    {
        if(null === $cart->getId()) {
            $cart = new Session();
            $cart->start();
            $cart->setId(uniqid());
        }
        return $this->render('admin/shopping/index.html.twig', ['products' => $productRepository->findAll(), 'cartId' => $cart->get('id')]);
    }

    /**
     * @Route("/superadmin/requirement/show/{reqId}", name="superadmin_requirement_show")
     */
    public function showRequirement($reqId)
    {
        return $this->redirectToRoute('admin_index');
    }

    /**
     * @Route("/superadmin/requirement/approve/{reqId}", name="superadmin_requirement_approve")
     */
    public function approveRequirement($reqId)
    {
        $em = $this->getDoctrine()->getManager();
        $requirement = $em->getRepository(Requirement::class)->findOneBy(['requirementNumber' => $reqId]);
        if (!$requirement) {
            throw $this->createNotFoundException('No existe tal requerimiento: '.$reqId);
        }
        $requirement->setStatus(Constant::TO_DO);
        $em->flush();

        return $this->redirectToRoute('superadmin_index');
    }

    /**
     * @Route("/superadmin/requirement/delete/{reqId}", name="superadmin_requirement_delete")
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

        return $this->redirectToRoute('superadmin_index');
    }

    /**
     * @Route("/superadmin/requirement/inprogress/{reqId}", name="superadmin_requirement_inprogress")
     * @Route("/admin/requirement/inprogress/{reqId}", name="admin_requirement_inprogress")
     */
    public function inProgressRequirement($reqId)
    {
        $em = $this->getDoctrine()->getManager();
        $requirement = $em->getRepository(Requirement::class)->findOneBy(['requirementNumber' => $reqId]);
        if (!$requirement) {
            throw $this->createNotFoundException('No existe tal requerimiento: '.$reqId);
        }
        $requirement->setStatus(Constant::IN_PROGRESS);
        $em->flush();

        if ($authChecker->isGranted('ROLE_SUPERADMIN')) {
            return $this->redirectToRoute('superadmin_index');
        }

        return $this->redirectToRoute('admin_index');
    }

    /**
     * @Route("/superadmin/requirement/inprogress/{reqId}", name="superadmin_requirement_finished")
     * @Route("/admin/requirement/inprogress/{reqId}", name="admin_requirement_finished")
     */
    public function finishedRequirement($reqId)
    {
        $em = $this->getDoctrine()->getManager();
        $requirement = $em->getRepository(Requirement::class)->findOneBy(['requirementNumber' => $reqId]);
        if (!$requirement) {
            throw $this->createNotFoundException('No existe tal requerimiento: '.$reqId);
        }
        $requirement->setStatus(Constant::FINISHED);
        $em->flush();

        if ($authChecker->isGranted('ROLE_SUPERADMIN')) {
            return $this->redirectToRoute('superadmin_index');
        }

        return $this->redirectToRoute('admin_index');
    }

    /**
     * @Route("/superadmin/requirement/delivered/{reqId}", name="superadmin_requirement_delivered")
     * @Route("/admin/requirement/delivered/{reqId}", name="admin_requirement_delivered")
     */
    public function deliveredRequirement($reqId)
    {
        $em = $this->getDoctrine()->getManager();
        $requirement = $em->getRepository(Requirement::class)->findOneBy(['requirementNumber' => $reqId]);
        if (!$requirement) {
            throw $this->createNotFoundException('No existe tal requerimiento: '.$reqId);
        }
        $requirement->setStatus(Constant::DELIVERED);
        $em->flush();

        if ($authChecker->isGranted('ROLE_SUPERADMIN')) {
            return $this->redirectToRoute('superadmin_index');
        }

        return $this->redirectToRoute('admin_index');
    }

    /**
     * @Route("/superadmin/requirement/edit/{reqId}", name="superadmin_requirement_edit")
     * @Route("/admin/requirement/edit/{reqId}", name="admin_requirement_edit")
     */
    public function editRequirement($reqId)
    {
        return $this->redirectToRoute('admin_index');
    }

    /**
     * @Route("/superadmin/product", name="superadmin_product_index", methods="GET")
     */
    public function productList(ProductRepository $productRepository)
    {
        return $this->render('admin/product/index.html.twig', ['products' => $productRepository->findAll()]);
    }

    /**
     * @Route("/superadmin/product/new", name="superadmin_product_new", methods="GET|POST")
     */
    public function newProduct(Request $request)
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();

            return $this->redirectToRoute('superadmin_product_index');
        }

        return $this->render('admin/product/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/superadmin/product/show/{id}", name="superadmin_product_show", methods="GET")
     */
    public function show(Product $product)
    {
        return $this->render('admin/product/show.html.twig', ['product' => $product]);
    }

    /**
     * @Route("/superadmin/product/edit/{id}", name="superadmin_product_edit", methods="GET|POST")
     */
    public function editProduct(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $product = $em->getRepository(Product::class)->find($id);

        if(!$product) {
            throw $this->createNotFoundException('No existe tal producto: '.$id);
        }
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'El producto fue editado.');

            return $this->redirectToRoute('superadmin_product_index');
        }

        return $this->render('admin/product/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/superadmin/product/delete/{id}", name="superadmin_product_delete")
     */
    public function deleteProduct(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $product = $em->getRepository(Product::class)->find($id);
        if(!$product) {
            throw $this->createNotFoundException('No existe tal producto: '.$id);
        }
        $em->remove($product);
        $em->flush();

        $this->addFlash('success', 'El producto fue eliminado.');

        return $this->redirectToRoute('superadmin_product_index');
    }
}
