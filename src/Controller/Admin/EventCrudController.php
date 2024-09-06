<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EventCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Event::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Événement')
            ->setEntityLabelInPlural('Événements')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des événements');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new("title", "Titre"),
            TextField::new("category", "Catégorie"),
            TextField::new("image", "Image"),
            TextField::new("content", "Contenu"),
            DateTimeField::new("date", "Date"),
        ];
    }
}
