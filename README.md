WST2 - Final Project

A pet grooming appointment management system. Customers register, save their pets, and book grooming appointments. Admins manage services and handle bookings.

Built with PHP, MySQL, and XAMPP.

Features:

Customer

Register and log in
Book an appointment (choose a saved pet or add a new one)
View and cancel their own appointments
Update their profile

Admin

Dashboard with appointment summaries
View all appointments and update their status
Add walk-in customers and appointments
Manage services (add, edit, hide)
Update their profile

Tech Stack

PHP
MySQL (phpMyAdmin)
XAMPP (Apache + MySQL)
HTML, CSS, JavaScript

Database

Four tables: users, services, pets, appointments.

A user can have many pets.
A pet can have many appointments.
A service can be booked in many appointments.
The owner of an appointment is found through its pet.
