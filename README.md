# LVNASTUDIO: collaborative custom jersey store

A multi-role e-commerce platform for ordering custom jerseys. Customers design and order, designers produce the artwork, agents handle orders and communication, and admins run the store, all in one system.

<img width="1170" height="672" alt="image" src="https://github.com/user-attachments/assets/4871a372-d98d-41c1-ae59-8a614331520b" /> <img width="1160" height="667" alt="image" src="https://github.com/user-attachments/assets/e725e868-9448-40ea-868f-dcb52115cecd" />
<img width="1168" height="668" alt="image" src="https://github.com/user-attachments/assets/0c592311-661f-4691-9754-8a99a46b00e0" /> <img width="1172" height="674" alt="image" src="https://github.com/user-attachments/assets/5c29dd6c-745d-40c3-b93f-ffa157d6bbd9" />




## Roles

| Role | What they can do |
|---|---|
| **Customer** | Browse the shop, customize a jersey, add to cart, check out, message an agent |
| **Agent** | Manage incoming orders, talk to customers, pass work to designers, keep a to-do list |
| **Designer** | Receive design requests, upload designs, save drafts, message agents |
| **Admin** | Manage products and the store, view sales analytics |

## Features

- **Jersey customizer** built on Fabric.js. Customers edit the design on a canvas, then save it to their order.
- **Shopping cart and checkout** with PayPal.
- **Messaging between roles** (customer ↔ agent, agent ↔ designer).
- **Design workflow.** Designers upload work and save drafts, and agents track progress.
- **Sales analytics** for admins with Chart.js.
- **Secure login** for all four roles from one sign-in page, with passwords hashed using PHP's `password_hash`.

## Tech stack

| Layer | Tools |
|---|---|
| Backend | PHP, MySQL |
| Frontend | HTML, CSS, JavaScript |
| Libraries | Fabric.js, Chart.js, PayPal JS SDK, Font Awesome |
| Local server | XAMPP |

## Running it locally

1. Install [XAMPP](https://www.apachefriends.org/) and start Apache and MySQL.
2. Clone this repo into `htdocs/`.
3. In phpMyAdmin, create a database called `lunadb` and import the schema.
4. Check the database settings in `connection.php`.
5. Open `http://localhost/<folder-name>/login.php`.

## Author

**Muhammad Naufal Mohd Nizam**, Intelligent System Engineering student at UiTM
[Portfolio](https://fvlnzm.my) · [LinkedIn](https://www.linkedin.com/in/muhammad-naufal-bin-mohd-nizam-0603b2279)
