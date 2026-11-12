<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/product')]
#[IsGranted('ROLE_STAFF')]
final class ProductController extends AbstractController
{
    #[Route('', name: 'app_product', methods: ['GET'])] 
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findBy([], ['createdAt' => 'DESC']);

        $addForm = $this->createForm(
            ProductType::class,
            new Product(),
            [
                'action' => $this->generateUrl('app_product_new'),
            ]
        )->createView();

        $editForms = [];

        foreach ($products as $product) {
            $editForms[$product->getId()] = $this->createForm(
                ProductType::class,
                $product,
                [
                    'action' => $this->generateUrl('app_product_edit', [
                        'id' => $product->getId(),
                    ]),
                ]
            )->createView();
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'addForm' => $addForm,
            'editForms' => $editForms,
            'activePage' => 'products',
        ]);
    }


    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $product = new Product();

        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $product->setCreatedBy($currentUser);

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if (!$imageFile) {
                $this->addFlash('error', 'Please upload a product image.');

                return $this->render('product/form.html.twig', [
                    'form' => $form,
                    'pageTitle' => 'Add Product',
                ]);
            }

            $newFilename = $this->uploadImage($imageFile, $slugger);

            $product->setImage('images/products/' . $newFilename);

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product created successfully.');

            return $this->redirectToRoute('app_product');
        }

        return $this->render('product/form.html.twig', [
            'form' => $form,
            'pageTitle' => 'Add Product',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        Product $product,
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $currentUser = $this->getUser();

        if (
            !$this->isGranted('ROLE_ADMIN')
            && (
                !$currentUser instanceof User
                || $product->getCreatedBy()?->getId() !== $currentUser->getId()
            )
        ) {
            throw $this->createAccessDeniedException(
                'You can only edit products you created.'
            );
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $newFilename = $this->uploadImage($imageFile, $slugger);

                $product->setImage('images/products/' . $newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Product updated successfully.');

            return $this->redirectToRoute('app_product');
        }

        return $this->render('product/form.html.twig', [
            'form' => $form,
            'pageTitle' => 'Edit Product',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function delete(
        Product $product,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();

        if (
            !$this->isGranted('ROLE_ADMIN')
            && (
                !$currentUser instanceof User
                || $product->getCreatedBy()?->getId() !== $currentUser->getId()
            )
        ) {
            throw $this->createAccessDeniedException(
                'You can only delete products you created.'
            );
        }

        if ($this->isCsrfTokenValid(
            'delete-product-' . $product->getId(),
            $request->request->get('_token')
        )) {
            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid security token. Product was not deleted.');
        }

        return $this->redirectToRoute('app_product');
    }

    private function uploadImage(
        UploadedFile $imageFile,
        SluggerInterface $slugger
    ): string {
        $originalFilename = pathinfo(
            $imageFile->getClientOriginalName(),
            PATHINFO_FILENAME
        );

        $safeFilename = $slugger->slug($originalFilename);

        $newFilename = $safeFilename . '-' . bin2hex(random_bytes(6))
            . '.' . $imageFile->guessExtension();

        $imageFile->move(
            $this->getParameter('kernel.project_dir') . '/public/images/products',
            $newFilename
        );

        return $newFilename;
    }
}
