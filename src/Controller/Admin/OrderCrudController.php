<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des commandes');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('orderType', 'Type de commande'),
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    "En cours" => "en-cours",
                    "Payée" => "payee",
                    "Annulée" => "annulee"
                ]),
            MoneyField::new('totalAmount', 'Montant total')
                ->setCurrency("EUR")
                ->setNumDecimals(2)
                ->formatValue(function ($value) {
                    return $value . " €";
                }),
            DateField::new('createdAt', 'Commande passée le')
                ->formatValue(function ($value) {
                    return $value->format("d/m/Y à H:i");
                }),
            CollectionField::new('product', 'Produits')
                ->onlyOnIndex()
        ];
    }
}
