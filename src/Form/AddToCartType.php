<?php

namespace App\Form;

use App\Entity\CartItem;
use App\Entity\PhysicalCreation;
use App\Entity\Product;
use App\Enum\SelectedType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire d'ajout au panier. Utilise les Form Events (PRE_SET_DATA / PRE_SUBMIT)
 * pour n'afficher les champs de mensurations QUE lorsque le produit est une
 * creation physique necessitant des mensurations : formulaire dynamique.
 */
class AddToCartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Product $product */
        $product = $options['product'];

        $builder
            ->add('selectedType', EnumType::class, [
                'class' => SelectedType::class,
                'label' => 'Type de produit',
                'choice_label' => fn (SelectedType $t) => $t->label(),
                'expanded' => true,
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantite',
                'attr' => ['min' => 1],
            ]);

        if ($product->isCustomizable()) {
            $builder->add('customizationNote', TextareaType::class, [
                'label' => 'Demande de personnalisation',
                'required' => false,
                'mapped' => true,
            ]);
        }

        // Ajout dynamique des champs de mensurations en fonction du produit.
        $needsMeasurements = $product instanceof PhysicalCreation && $product->requiresMeasurements();

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($needsMeasurements) {
            if ($needsMeasurements) {
                $this->addMeasurementFields($event->getForm());
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($needsMeasurements) {
            $data = $event->getData();
            // On ne demande les mensurations que si l'utilisateur a choisi la creation physique.
            if ($needsMeasurements && ($data['selectedType'] ?? null) === SelectedType::Physical->value) {
                $this->addMeasurementFields($event->getForm());
            }
        });
    }

    private function addMeasurementFields(FormInterface $form): void
    {
        foreach (['poitrine' => 'Tour de poitrine (cm)', 'taille' => 'Tour de taille (cm)', 'hanches' => 'Tour de hanches (cm)', 'hauteur' => 'Hauteur souhaitee (cm)'] as $name => $label) {
            $form->add('m_'.$name, IntegerType::class, [
                'label' => $label,
                'required' => false,
                'mapped' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CartItem::class,
            'csrf_protection' => true,
        ]);
        $resolver->setRequired('product');
        $resolver->setAllowedTypes('product', Product::class);
    }
}
