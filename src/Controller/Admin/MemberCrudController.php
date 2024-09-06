<?php

namespace App\Controller\Admin;

use App\Entity\Member;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MemberCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Member::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Membre')
            ->setEntityLabelInPlural('Membres')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des membres');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new("firstName", "Prénom"),
            TextField::new("lastName", "Nom de famille"),
            TextField::new("sex", "Prénom"),
            TextField::new("birthDate", "Date de naissance"),
            TextField::new("address", "Adresse")
                ->hideOnIndex(),
            TextField::new("postalCode", "Code postal")
                ->hideOnIndex(),
            TextField::new("city", "Ville"),
            AssociationField::new("club", "Club")
                ->setFormTypeOption('choice_label', 'name')
                ->formatValue(function ($value, $entity) {
                    return $entity->getClub() ? $entity->getClub()->getName() : '';
                }),
            AssociationField::new("commande", "Commande")
                ->setFormTypeOption('choice_label', 'id')
                ->formatValue(function ($value, $entity) {
                    return 'Commande N° ' . $entity->getCommande()->getId();
                }),
            TextField::new("email", "Courriel"),
        ];
    }
}
