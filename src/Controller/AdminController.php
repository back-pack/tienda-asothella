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
use App\Entity\RoofTile;
use App\Entity\Product;
use App\Form\RequirementType;
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

    /**
     * @Route("/admin/shopping/additem/{itemId}", name="admin_shopping_additem")
     * @Route("/superadmin/shopping/additem/{itemId}", name="superadmin_shopping_additem")
     */
    public function addItem(Request $request, $itemId, Session $cart)
    {
        if(null === ($cart->get('id'))) {
            return $this->redirectToRoute('superadmin_shopping');
        }
        $product = $this->getDoctrine()->getRepository(Product::class)->findOneBy(['uid' => $itemId]);
        $productRequest = new ProductRequest();
        $form = $this->createForm(ProductRequestType::class, $productRequest);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            
            $cart->set('requestId', uniqid());
            $cart->set('item', $productRequest);

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
     * @Route("/admin/shopping/viewitem/{itemId}", name="admin_shopping_viewitem")
     * @Route("/superadmin/shopping/viewitem/{itemId}", name="superadmin_shopping_viewitem")
     */
    public function viewItem(Request $request)
    {
        return $this->render('admin/shopping/addItem.html.twig');
    }

    /**
     * @Route("/admin/shopping", name="admin_shopping")
     * @Route("/superadmin/shopping", name="superadmin_shopping")
     */
    public function shopping(ProductRepository $productRepository)
    {
        if(!isset($cart)) {
            $cart = new Session();
            $cart->set('id', uniqid());
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
