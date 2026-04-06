# LOOPI - Eco-Civic Platform

A Symfony-based web application for an eco-friendly art recycling platform.

## Setup

1. Install dependencies:
   ```bash
   composer install
   ```

2. Set up the database:
   - Create a MySQL database named `loopi_db`
   - Import the `loopi.sql` file into the database

3. Configure environment:
   - Update `.env` with your database credentials if needed

4. Run the application:
   ```bash
   symfony serve
   ```

## Routes

- `/` - Landing page
- `/admin` - Admin backoffice
- `/organisateur` - Organizer dashboard
- `/participant` - Participant dashboard

## Features

- User management with roles (admin, organizer, participant)
- Event management
- Product gallery
- Donations and collections
- Feedback system

## Technologies

- Symfony 6.4
- Doctrine ORM
- MySQL
- Twig templates