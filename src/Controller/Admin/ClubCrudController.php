<?php


namespace App\Controller\Admin;

use App\Entity\Club;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ClubCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Club::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name', 'Nom du club'),
            ImageField::new('logo', 'Logo')
                ->setBasePath('/uploads/')
                ->setUploadDir('public/uploads/')
                //->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(false),
            TextField::new('address', 'Adresse'),
            TextField::new('postalCode', 'Code postal'),
            TextField::new('city', 'Ville'),
            TextField::new('presidentName', 'Nom du président'),
            TextField::new('treasurerName', 'Nom du trésorier'),
            TextField::new('email', 'Email du club'),
            TextField::new('country', 'Pays'),
            TextField::new('clubNumber', 'Numéro de club'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Club')
            ->setEntityLabelInPlural('Clubs')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des clubs');
    }
}
