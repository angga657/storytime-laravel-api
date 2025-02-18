# Storytimes

This is a project built using Laravel 10


## Features

- Authentication

- CRUD off Books

- Create and Delete Bookmarks

- Get Books Data by User

- Get Books Data by Category

- Get Books Data by sort Popular of Bookmarks count

- Get Books Data by sort Descending of Title books

- Get Books Data by sort Ascending of Title Books

- Get Books Data by sort Newest of books latest

## System Requirements

- Laravel framework version 10.0

- PHP version 8.1

- Composer

- MySQL

- Postman

### Instructions

1. Configure the database in the .env file:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=storytime
DB_USERNAME=your_username
DB_PASSWORD=your_password

2. Run the database migrations:

php artisan migrate

3. Run the CategorySeeder to populate initial data:

php artisan db:seed --class=CategorySeeder

4. Start the Laravel development server:

php artisan serve

## API Access:

### Account

- localhost:8000/api/register (Access the POST API of register)

- localhost:8000/api/login (Access the POST API of Login)

- localhost:8000/api/logout (Access POST API of Logout account)

- localhost:8000/api/user (Access the GET API of User Data)

- localhost:8000/api/edit-profile (Access PUT API of Profile edit)

- localhost:8000/api/upload-image (Access POST API of Upload Profile image in edit profile page)


### Books
#### This API use APIResource to access the Books 

- localhost:8000/api/books (Access APIResource of Books)
 

#### Another API Get Data in Books

- localhost:8000/api/books-user/{id} (Access Get Data Books By User ID)

- localhost:8000/api/books-category (Access Get Data Books By Category)

#### Books Features

- localhost:8000/api/books?sort=popular (Access Books data with category count)

- localhost:8000/api/books?sort=a-z (Access Books Title in Descending order)

- localhost:8000/api/books?sort=z-a (Access Books Title in Ascending order)

- localhost:8000/api/books?sort=newest (Access Books By Latest Date)

This api sort can be combined with id_category
#### Example: 

- localhost:8000/api/books?sort=a-z&id_category=2

### Categories
#### This API use APIResource to access the Categories 

- localhost:8000/api/categories (Access APIResource of Categories)

### Bookmarks
#### This API use APIResource to access the Bookmarks

- localhost:8000/api/bookmarks (Access APIResource of Bookmarks)