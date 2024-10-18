<?php

namespace App\Controller\Admin;

use App\Entity\Member;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
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
            TextField::new("address", "Adresse")
                ->hideOnIndex(),
            TextField::new("postalCode", "Code postal")
                ->hideOnIndex(),
            TextField::new("city", "Ville")
                ->hideOnIndex(),
            TextField::new("email", "Courriel"),
        ];
    }
}
