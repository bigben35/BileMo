# BileMo API - Project 7 OpenClassrooms

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/0b8d754e980b47afbb53006c7e672f12)](https://app.codacy.com/gh/bigben35/BileMo/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)

# Description
BileMo is a company offering a selection of high-end mobile phones.
I am responsible for developing the showcase of mobile phones for the BileMo company. BileMo's business model is not to directly sell its products on the website, but to provide access to the catalog to any platforms that wish to via an API (Application Programming Interface). It is therefore exclusively B2B (business to business) sales.

# Prerequisites
PHP 8.0.12  
Symfony 6

# Installation
Clone the Git repository to your machine : **git clone** https://github.com/bigben35/BileMo.git  
Navigate to the project folder : **cd mon-projet-symfony**  
Install dependencies with Composer : **composer install**  

Create the jwt folder in the config folder (/config/jwt)  

Generate your public and private keys with these 2 commands :   
**openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096**  
**openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout**  

Remember to note your passwords !  

Run **composer require symfony/console** to use the symfony console command instead of php/bin console (personal preference). 
Create a database in PhpMyAdmin (for example) with the desired name. It will be used in the .env file to establish the connection between the application and the database. Duplicate the .env file and name it .env.local. For security reasons, this is where you will put your connection information.  

Create the database : **symfony console doctrine:database:create**    
Run migrations : **symfony console doctrine:migrations:migrate**  
Load fixtures (demo data) : **symfony console doctrine:fixtures:load**, to have data (Clients, Users, and Phones).  
Start the Symfony server : **symfony server:start**  

And there you go! You can now access the application by navigating to http://localhost:8000 in your browser.  
You can switch to https with the command : **symfony server:ca:install**

# Documentation
The documentation for using the API is available here https://localhost:8000/api/doc

# Authentication
To obtain an authentication token, here is the route: https://localhost:8000/api/login_check  
Here is one of the Client accounts (to be entered in the body of Postman for example) :   
{
    "username" : "company1@gmail.com",
    "password": "password"
}
