# BileMo API - Projet 7 OpenClassrooms

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/0b8d754e980b47afbb53006c7e672f12)](https://app.codacy.com/gh/bigben35/BileMo/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)

# Description
BileMo est une entreprise offrant toute une sélection de téléphones mobiles haut de gamme.
Je suis en charge du développement de la vitrine de téléphones mobiles de l’entreprise BileMo. Le business modèle de BileMo n’est pas de vendre directement ses produits sur le site web, mais de fournir à toutes les plateformes qui le souhaitent l’accès au catalogue via une API (Application Programming Interface). Il s’agit donc de vente exclusivement en B2B (business to business).

# Prérequis
PHP 8.0.12  
Symfony 6

# Installation
Cloner le dépôt Git sur votre machine : **git clone** https://github.com/bigben35/BileMo.git  
Naviguer dans le dossier du projet : **cd mon-projet-symfony**  
Installer les dépendances avec Composer : **composer install**  
Créer le dossier jwt dans le dossier config (/config/jwt)  
Générer vos clés publiques et privées avec ces 2 commandes :   
**openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096**  
**openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout**  
Pensez à noter vos passwords !
Faire un **composer require symfony/console** pour pouvoir utiliser la commande symfony console au lieu de php/bin console (au choix de la personne). 
Créer une base de données dans PhpMyAdmin (par exemple) avec le nom souhaité. Il sera utilisé dans le fichier .env pour permettre la connexion entre l'application et la base de données. Dupliquer le fichier .env et nommez-le .env.local. Pour une question de sécurité, c'est ici que vous allez mettre vos informations de connexions.  Créer la base de données : **symfony console doctrine:database:create**    
Effectuer les migrations : **symfony console doctrine:migrations:migrate**  
Charger les fixtures (données de démonstration) : **symfony console doctrine:fixtures:load**, pour avoir des données (Clients, Utilisateurs et Téléphones).  
Démarrer le serveur Symfony : **symfony server:start**  

Et voilà ! Vous pouvez maintenant accéder à l'application en naviguant vers http://localhost:8000 dans votre navigateur.  
Vous pouvez passer en https avec la commande : **symfony server:ca:install**

# Documentation
La Documentation pour l'utilisation de l'API est disponible ici https://localhost:8000/api/doc

# Authentification
Pour obtenir un token d'authentification, voici la route: https://localhost:8000/api/login_check  
Voici un des comptes Client ( à mettre dans le body de Postman par exemple) :   
{
    "username" : "company1@gmail.com",
    "password": "password"
}
