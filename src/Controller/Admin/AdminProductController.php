<?php

namespace App\Controller\Admin;

use App\Entity\DigitalPattern;
use App\Entity\PhysicalCreation;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Gestion des produits reservee au super-admin (conformement au cahier des
 * charges : la gestion complete des produits releve de ROLE_SUPER_ADMIN).
 */
#[IsGranted('ROLE_SUPER_ADMIN')]
#[Route('/admin/produits')]
class AdminProductController extends AbstractController
{
    #[Route('', name: 'admin_product_index', methods: ['GET'])]
    public function index(ProductRepository $products): Response
    {
        return $this->render('admin/products/index.html.twig', [
            'products' => $products->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/nouveau', name: 'admin_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        // Le sous-type est choisi via ?type=physical|pattern (Product est abstraite).
        $type = $request->query->get('type', 'physical');
        $product = $type === 'pattern' ? new DigitalPattern() : new PhysicalCreation();

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setSlug($slugger->slug((string) $product->getName())->lower()->toString());
            $this->applyMainImage($product, $form->get('imageFilename')->getData());
            $em->persist($product);
            $em->flush();
            $this->addFlash('success', 'Produit cree.');

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/products/form.html.twig', [
            'form' => $form,
            'product' => $product,
            'mode' => 'new',
        ]);
    }

    #[Route('/{id}/editer', name: 'admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(Product $product, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setSlug($slugger->slug((string) $product->getName())->lower()->toString());
            $this->applyMainImage($product, $form->get('imageFilename')->getData());
            $product->touch();
            $em->flush();
            $this->addFlash('success', 'Produit mis a jour.');

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/products/form.html.twig', [
            'form' => $form,
            'product' => $product,
            'mode' => 'edit',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_product_delete', methods: ['POST'])]
    public function delete(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_product'.$product->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $em->remove($product);
            $em->flush();
            $this->addFlash('success', 'Produit supprime.');
        } catch (ForeignKeyConstraintViolationException) {
            // Le produit est reference par une commande ou un panier : on ne le
            // supprime pas (on pourrait plutot le depublier).
            $this->addFlash('error', 'Impossible de supprimer : ce produit est lie a des commandes. Depubliez-le plutot.');
        }

        return $this->redirectToRoute('admin_product_index');
    }

    /**
     * Associe (ou remplace) l'image principale du produit a partir d'un nom de
     * fichier present dans public/uploads/. Upload de fichier reel = evolution
     * possible (VichUploaderBundle), non branche ici pour rester simple.
     */
    private function applyMainImage(Product $product, ?string $filename): void
    {
        $filename = trim((string) $filename);
        if ($filename === '') {
            return;
        }

        foreach ($product->getImages() as $existing) {
            if ($existing->isMain()) {
                $existing->setFilename($filename);

                return;
            }
        }

        $product->addImage((new ProductImage())->setFilename($filename)
            ->setAltText($product->getName())->setIsMain(true)->setPosition(0));
    }
}
