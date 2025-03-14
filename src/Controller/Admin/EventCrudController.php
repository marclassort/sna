<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;

class EventCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Event::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular("Événement")
            ->setEntityLabelInPlural("Événements")
            ->setPageTitle(Crud::PAGE_INDEX, "Gestion des événements");
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new("title", "Titre"),
            TextField::new("category", "Catégorie"),
            ImageField::new('image', 'Image')
                ->setBasePath('/uploads/')
                ->setUploadDir('public/uploads/')
                ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
                ->setFormType(FileUploadType::class)
                ->setRequired(false)
                ->setFormTypeOption('allow_delete', false),
            TextField::new("imageAlt", "Description de l'image"),
            TextEditorField::new("content", "Contenu"),
            TextField::new("date", "Date"),
            DateTimeField::new('eventDate', 'Date de l\'événement')
                ->setFormat('dd/MM/yyyy HH:mm'),
            ChoiceField::new("status", "Statut")
                ->setChoices([
                    "Publié" => "publish",
                    "Brouillon" => "draft"
                ])
                ->renderAsBadges([
                    "publish" => "success",
                    "draft" => "warning"
                ])
        ];
    }
}
