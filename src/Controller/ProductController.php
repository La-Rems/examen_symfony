<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Form\AddProductFormType;
use App\Form\RegistrationFormType;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductController extends AbstractController
{
    /**
     * @Route("/addProduct", name="add_new_product")
     */
    public function index(Request $request, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $productForm = $this->createForm(AddProductFormType::class, $product);
        $productForm->handleRequest($request);

        $user = $this->getUser();

        if ($productForm->isSubmitted() && $productForm->isValid()) {
            $product->setName($productForm->get('name')->getData());
            $product->setPrice($productForm->get('price')->getData());
            $product->setCreatedAt(new \DateTime());
            $product->setDescription($productForm->get('description')->getData());

            $image = $productForm->get('picture')->getData();
            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();

                try {
                    $image->move(
                        $this->getParameter('pictures_directory'),
                        $newFilename);
                } catch (FileException $exception) {
                    throwException($exception);
                }

                $product->setPicture($newFilename);
            }

            $isAvaible = $productForm->get('available')->getData();

            if($isAvaible){
                $product->setAvailable(false);
            } else {
                $product->setAvailable(true);
            }
            $product->setSeller($user);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($product);
            $entityManager->flush();
            return $this->redirectToRoute('dashboard');
        }
        return $this->render('product/add.html.twig', [
            'productForm' => $productForm->createView()
        ]);
    }

    /**
     * @Route("/updateProduct/{id}", name="update_product")
     */
    public function update(Request $request, String $id): Response
    {
        $productId = intval($id);
        $entityManager = $this->getDoctrine()->getManager();
        $product = $entityManager->getRepository(Product::class)->find($productId);

        if($product->getAvailable()){
            $product->setAvailable(false);
        } else if (!$product->getAvailable()) {
            $product->setAvailable(true);
        }
        $entityManager->flush();
        return $this->redirectToRoute('dashboard');
    }

    /**
     * @Route("/api/productsPage", name="api_products_page")
     * Exemple : http://127.0.0.1:8000/api/productsPage?page=2
     */
    public function getProductsAvailable(PaginatorInterface $paginator, Request $request, ProductRepository $productRepository, SerializerInterface $serializer): Response
    {
        $data = $this->getDoctrine()->getRepository(Product::class)->findBy(['available' => true]);

        $products = $paginator->paginate(
            $data, $request->query->getInt('page', 1), 20);
        $data = $serializer->serialize($products, 'json',  ['groups' => 'api_products']);
        return new JsonResponse($data, 201, [], true);
    }

    /**
     * @Route("/api/{id}/products", name="api_products_available")
     */
    public function getProductByUser(String $id, ProductRepository $productRepository, UserRepository $userRepository, Request $request, SerializerInterface $serializer): JsonResponse
    {
        $userId = intval($id);
        $user = $userRepository->findBy(['id' => $userId]);
        $products = $productRepository->findBy(['seller' => $user, 'available' => true]);
        $data = $serializer->serialize($products, 'json',  ['groups' => 'api_products']);
        return new JsonResponse($data, 201, [], true);
    }
}
