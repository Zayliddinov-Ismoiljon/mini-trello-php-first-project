# Mini Trello

Loyihalar va vazifalarni boshqarish tizimi. Sof PHP, PDO va MySQL da, frameworksiz yozilgan.

Tizimda uch xil rol bor. **Admin** butun tizimni boshqaradi: foydalanuvchilar, rollar va ularning ruxsatlari. **Ish boshqaruvchi** o'z loyihalariga mas'ul, ularning holati va jamoasini kuzatadi. **Xodim** o'ziga biriktirilgan vazifalarni bajaradi. Har bir rolga ruxsatlar alohida biriktiriladi, shuning uchun har kim faqat o'ziga tegishli sahifa, tugma va ma'lumotlarni ko'radi.

**Asosiy imkoniyatlar:** login va sessiyalar, rol va ruxsatlar (RBAC), foydalanuvchilar, loyihalar va vazifalar uchun CRUD, qidiruv va filterlar, pagination, admin va ish boshqaruvchi uchun alohida dashboardlar.

**Texnologiyalar:** PHP 8, PDO, MySQL, Bootstrap 5.

---

## Ma'lumotlar bazasi

```mermaid
erDiagram
    ROLES ||--o{ USERS : "rolga ega"
    ROLES ||--o{ ROLE_PERMISSIONS : ""
    PERMISSIONS ||--o{ ROLE_PERMISSIONS : ""
    USERS ||--o{ PROJECTS : "mas'ul"
    PROJECTS ||--o{ TASKS : "ichida"
    USERS ||--o{ TASKS : "bajaradi"

    ROLES {
        int id PK
        string role_name
        int status
    }
    USERS {
        int id PK
        int role_id FK
        string user_name
        string login
        string password
        int status
    }
    PERMISSIONS {
        int id PK
        string code
        string description
        string content_type
    }
    ROLE_PERMISSIONS {
        int role_id PK
        int permission_id PK
    }
    PROJECTS {
        int id PK
        int user_id FK
        string project_name
        int status
    }
    TASKS {
        int id PK
        int project_id FK
        int user_id FK
        string task_name
        date deadline
        int status
    }
```

`role_permissions` bog'lovchi jadval: bitta rolda ko'p ruxsat, bitta ruxsat ko'p rolda bo'lishi mumkin.

## Rollar va ruxsatlar

```mermaid
flowchart LR
    U[Foydalanuvchi] --> R[Rol]
    R --> P1[task_menu]
    R --> P2[project_menu]
    R --> P3[users_menu]
    R --> P4[...]
```

Ruxsatlar kodga emas, bazaga yozilgan. Admin rolga yangi ruxsat bersa, u darhol kuchga kiradi, kodni o'zgartirish shart emas.

## So'rov qanday tekshiriladi

```mermaid
flowchart TD
    A[So'rov keldi] --> B{Login qilinganmi?}
    B -- Yo'q --> L[Login sahifasiga]
    B -- Ha --> C{Rolda bu amalga<br/>ruxsat bormi?}
    C -- Yo'q --> X[Ruxsat yo'q]
    C -- Ha --> D{Yozuv shu userga<br/>tegishlimi yoki u admin?}
    D -- Yo'q --> X
    D -- Ha --> E[Amal bajariladi]
```

Uch bosqichli himoya: **kirganmi** (sessiya), **qila oladimi** (rol ruxsati), **aynan shu yozuv ustida qila oladimi** (egalik). Tekshiruvlar server tomonida bajariladi, interfeysda tugmalarni yashirish faqat qulaylik uchun.

## Ishga tushirish

```bash
composer install
php -S localhost:8000 -t public
```

`config/database.example.php` dan nusxa olib `config/database.php` yarating va baza ma'lumotlarini yozing.
