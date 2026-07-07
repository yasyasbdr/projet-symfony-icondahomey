<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\DigitalPattern;
use App\Entity\PhysicalCreation;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de création/édition d'un produit.
 * Product étant abstraite (Single Table Inheritance), le formulaire ajoute
 * dynamiquement les champs spécifiques au sous-type réel (PhysicalCreation ou
 * DigitalPattern) grâce à l'événement PRE_SET_DATA.
 */
class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 5],
            ])
            ->add('basePrice', TextType::class, [
                'label' => 'Prix (€)',
                // Champ texte + normalisation : accepte la virgule ET le point.
                'attr' => ['inputmode' => 'decimal', 'placeholder' => 'Ex : 54,00'],
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Publié (visible en boutique)',
                'required' => false,
            ])
            ->add('isCustomizable', CheckboxType::class, [
                'label' => 'Personnalisable',
                'required' => false,
            ])
            ->add('imageFilename', TextType::class, [
                'label' => 'Photo principale (nom du fichier dans public/uploads/)',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'Ex : sac1.jpg'],
            ]);

        // Normalise le prix saisi : "54,00" ou "54.00" -> "54.00" (format base de donnees).
        $builder->get('basePrice')->addModelTransformer(new CallbackTransformer(
            static fn (?string $model): string => $model ?? '',
            static fn (?string $value): string => str_replace(',', '.', trim((string) $value)),
        ));

        // Champs spécifiques au sous-type, ajoutés selon l'objet réel.
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $product = $event->getData();
            $form = $event->getForm();

            if ($product instanceof PhysicalCreation) {
                $form->add('stock', IntegerType::class, [
                    'label' => 'Stock disponible',
                    'required' => false,
                ]);
                $form->add('requiresMeasurements', CheckboxType::class, [
                    'label' => 'Nécessite des mensurations (vêtement)',
                    'required' => false,
                ]);
            } elseif ($product instanceof DigitalPattern) {
                $form->add('difficultyLevel', ChoiceType::class, [
                    'label' => 'Niveau de difficulté',
                    'choices' => [
                        'Débutant' => 'debutant',
                        'Intermédiaire' => 'intermediaire',
                        'Avancé' => 'avance',
                    ],
                ]);
                $form->add('pageCount', IntegerType::class, [
                    'label' => 'Nombre de pages',
                    'required' => false,
                ]);
                $form->add('pdfFilename', TextType::class, [
                    'label' => 'Nom du fichier PDF',
                    'required' => false,
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
