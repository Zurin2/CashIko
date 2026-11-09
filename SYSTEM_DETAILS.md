# CashIko — System Details

A complete overview of how CashIko works, its core features, and its parts.

---

## 1. What CashIko Is

**CashIko** is a retail management system created for micro and small store businesses (such as neighborhood sari-sari stores, retail shops, and multi-branch stores). It tracks daily sales, inventory, business expenses, customer credit balances (*utang*), and payment reminders.

---

## 2. Main Features

* **Daily Sales & Checkout:** Record sales by selecting products, entering quantities, and choosing payment types (Cash, GCash, Maya, or Credit / Utang).
* **Inventory & Stock Tracking:** Track product quantities, unit costs, selling prices, expiration dates, low-stock warnings, and restocking history.
* **Credit & Ledger (Utang System):** Record customer credit, track partial or full payments, view due dates, and monitor trusted vs. risky customer tags.
* **Payment Reminders:** Create customer text reminder messages using gentle or firm tones.
* **Expense Tracker:** Log daily shop expenses by category (e.g., rent, utilities, supplies) to compute true daily and weekly net profit.
* **Reports & Daily Summary:** View total daily sales, profit margins, top-selling items, and recent shop activities.
* **Multi-Branch Support:** Manage more than one branch location under a single business account.
* **AI Business Assistant:** In-app chat assistant that provides shop advice, answers questions, and summarizes daily business numbers.
* **Superadmin Area:** Manage registered businesses, subscription plans (Basic, Premium), and system status.

---

## 3. Technology Used

| Component | Tool / Library | Role |
| :--- | :--- | :--- |
| **Backend Framework** | Laravel 13 | Handles server logic, data rules, and database actions |
| **Frontend Framework** | Vue 3 | Displays the user interface and interactive pages |
| **Connection Layer** | Inertia.js 2 | Connects Laravel to Vue without needing separate API routes |
| **Styling** | Tailwind CSS 3 & 4 | Provides UI design and mobile-friendly layouts |
| **Icons** | Lucide Vue Next | App icons and visual symbols |
| **Database** | SQLite (or MySQL) | Stores all application records |
| **Build Tool** | Vite 8 | Compiles frontend styles and Vue components |

---

## 4. Database Records (Data Structure)

The system organizes information into the following main tables:

| Name | Purpose | Key Details Stored |
| :--- | :--- | :--- |
| `users` | User accounts | Name, email address, password, superadmin status |
| `businesses` | Store profiles | Business name, type, plan, location, settings |
| `branches` | Store locations | Branch name, address, active status |
| `products` | Store inventory items | Name, unit, quantity, cost price, selling price, low-stock alerts |
| `customers` | Regular shoppers & buyers | Customer name, phone number, customer tag (trusted, watch, none) |
| `sales` | Completed sales transactions | Sale date/time, items sold, payment type, total amount, profit |
| `utang_entries` | Credit and payment history | Customer ID, amount, entry type (credit or payment), due date |
| `expenses` | Daily business costs | Expense category, amount spent, date, notes |
| `stock_movements` | Stock audit trail | Product ID, restock/sale/adjustment type, quantity added or removed |
| `reminders` | Due date notices | Customer ID, generated text, tone (gentle / firm), status |
| `chat_messages` | Assistant chat history | Chat role (user / assistant), message text |

---

## 5. Folder Structure

```text
cashiko/
├── app/
│   ├── Http/Controllers/    # Receives user actions and prepares page responses
│   ├── Models/                 # Defines data objects and database connections
│   ├── Services/               # Complex logic (sales analytics, assistant, demo data)
│   └── Support/                # Helpers for dates, periods, and branch selection
├── database/
│   ├── migrations/             # Creates and updates database tables
│   └── seeders/                # Fills the database with initial sample data
├── resources/
│   ├── js/
│   │   ├── Components/         # Reusable buttons, inputs, modals, and navigation bars
│   │   ├── Layouts/            # Main page frames and menu sidebars
│   │   └── Pages/              # Full screens (Home, Sales, Stock, Utang, Reports, Settings)
│   └── css/                    # Global app styles
├── routes/
│   └── web.php                 # Web page addresses and access rules
└── public/                     # Public files (images, built assets)
```

---

## 6. User Roles and Access

1. **Store Owner / User:**
   * Registers a store.
   * Sees only their own store records (data is isolated per business).
   * Manages daily sales, stocks, credit records, expenses, and branches.

2. **Superadmin:**
   * Global access.
   * Reviews all registered stores, active plans, and platform health.
