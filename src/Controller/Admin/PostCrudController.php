<?php

namespace App\Controller\Admin;

use App\Entity\Post;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;

class PostCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Post::class;
    }


    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Titre');
        yield TextField::new('author', 'Auteur');
        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('title');
        yield TextareaField::new('excerpt', 'Résumé')
            ->setMaxLength(250)
            ->setCustomOption('minLength', 80);
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Publié' => 'publish',
                'Brouillon' => 'draft',
                'Corbeille' => 'trash'
            ]);
        yield DateField::new('createdAt', 'Date de création');
        yield DateField::new('modifiedAt', 'Date de modification')
            ->onlyOnForms();
        yield AssociationField::new('categories', 'Catégories');
        yield ImageField::new('image', 'Image')
            ->setBasePath('/uploads/')
            ->setUploadDir('public/uploads/')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setFormType(FileUploadType::class)
            ->setFormTypeOption('allow_delete', false);
        yield TextEditorField::new('content', 'Contenu')
            ->onlyOnForms();
    }
}
