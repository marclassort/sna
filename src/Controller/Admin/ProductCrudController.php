<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des produits');
    }

    public function configureFields(string $pageName): iterable
    {
        $imageField = ImageField::new('image', 'Image')
            ->setBasePath('/uploads/')
            ->setUploadDir('public/uploads/')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setFormType(FileUploadType::class)
            ->setFormTypeOption('required', false);

        if ($pageName === Crud::PAGE_EDIT) {
            $imageField->setFormTypeOption('mapped', false)
                ->setHelp('Laissez vide pour conserver l\'image actuelle.');
        }

        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name', 'Nom du produit'),
            TextField::new('category', 'Catégorie'),
            TextField::new('description', 'Description'),
            TextField::new('price', 'Prix'),
            $imageField,
        ];
    }
}
