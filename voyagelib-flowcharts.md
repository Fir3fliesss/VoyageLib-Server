# VoyageLib - Flowcharts Sistem Perpustakaan Digital

## 1. Architecture Overview

```mermaid
graph TB
    subgraph FE["Frontend - React"]
        UI[User Interface]
        AuthUI[Auth Pages]
        UserDash[User Dashboard]
        StaffDash[Staff Dashboard]
        AdminDash[Admin Dashboard]
    end
    
    subgraph BE["Backend - Laravel API"]
        API[REST API]
        AuthMW[Auth Middleware]
        RoleMW[Role Middleware]
        BookCtrl[Book Controller]
        BorrowCtrl[Borrow Controller]
        UserCtrl[User Controller]
        ReportCtrl[Report Controller]
    end
    
    subgraph SB["Supabase"]
        Auth[Authentication]
        DB[(PostgreSQL)]
    end
    
    subgraph EXT["External API"]
        Gutendex[Gutendex API]
    end
    
    FE --> API
    AuthUI --> Auth
    API --> AuthMW
    AuthMW --> RoleMW
    RoleMW --> BookCtrl & BorrowCtrl & UserCtrl & ReportCtrl
    
    AuthMW <--> Auth
    BookCtrl & BorrowCtrl & UserCtrl & ReportCtrl <--> DB
    BookCtrl --> Gutendex
    
    style FE fill:#e3f2fd
    style BE fill:#fff3e0
    style SB fill:#e8f5e9
    style EXT fill:#f3e5f5
```

---

## 2. Authentication Flow

```mermaid
flowchart TD
    Start([User Accesses App]) --> CheckAuth{User<br/>Authenticated?}
    
    CheckAuth -->|No| LoginPage[Show Login/Register Page]
    LoginPage --> UserChoice{User Action}
    
    UserChoice -->|Login| InputLogin[Input Email & Password]
    UserChoice -->|Register| InputReg[Input Registration Data]
    
    InputLogin --> SupaLogin[Supabase Auth Login]
    SupaLogin --> LoginCheck{Login<br/>Success?}
    LoginCheck -->|Yes| GetToken[Get Access Token]
    LoginCheck -->|No| LoginError[Show Error Message]
    LoginError --> LoginPage
    
    InputReg --> SupaReg[Supabase Auth Register]
    SupaReg --> RegCheck{Register<br/>Success?}
    RegCheck -->|Yes| CreateUser[Create User Record in DB]
    RegCheck -->|No| RegError[Show Error Message]
    RegError --> LoginPage
    
    CreateUser --> AssignRole[Assign Default Role: User]
    AssignRole --> GetToken
    
    GetToken --> SaveToken[Save Token to LocalStorage]
    SaveToken --> RedirectRole
    
    CheckAuth -->|Yes| ValidateToken{Token<br/>Valid?}
    ValidateToken -->|No| ClearToken[Clear Token]
    ClearToken --> LoginPage
    ValidateToken -->|Yes| RedirectRole
    
    RedirectRole{Check User Role}
    RedirectRole -->|Admin| AdminHome[Admin Dashboard]
    RedirectRole -->|Staff| StaffHome[Staff Dashboard]
    RedirectRole -->|User| UserHome[User Dashboard]
    
    AdminHome & StaffHome & UserHome --> End([Authenticated Session])
    
    style Start fill:#4caf50
    style End fill:#4caf50
    style LoginPage fill:#fff9c4
    style AdminHome fill:#e1bee7
    style StaffHome fill:#b3e5fc
    style UserHome fill:#c8e6c9
```

---

## 3. Book Management Flow

```mermaid
flowchart TD
    Start([User Opens Book List]) --> FetchBooks[Fetch Books from System]
    
    FetchBooks --> LocalBooks[Get Local Books from DB]
    FetchBooks --> APIBooks[Get Books from Gutendex API]
    
    LocalBooks --> ProcessLocal{Process<br/>Local Books}
    ProcessLocal --> AddLocalMeta[Add Metadata:<br/>- can_read_online: false<br/>- stock: from DB<br/>- source: local]
    
    APIBooks --> ProcessAPI{Process<br/>API Books}
    ProcessAPI --> AddAPIMeta[Add Metadata:<br/>- can_read_online: true<br/>- stock: unlimited<br/>- source: gutendex]
    
    AddLocalMeta --> MergeBooks[Merge Book Lists]
    AddAPIMeta --> MergeBooks
    
    MergeBooks --> ApplyFilter{User Applied<br/>Filter?}
    ApplyFilter -->|Yes| FilterBooks[Filter by Category/Search]
    ApplyFilter -->|No| DisplayAll[Display All Books]
    FilterBooks --> DisplayAll
    
    DisplayAll --> UserAction{User Action}
    
    UserAction -->|Click Book| ShowDetail[Show Book Detail]
    ShowDetail --> CheckReadable{Can Read<br/>Online?}
    
    CheckReadable -->|Yes| ShowRead[Show Read Online Button]
    ShowRead --> ReadBook[Open Gutendex Reader]
    
    CheckReadable -->|No| CheckStock{Stock > 0?}
    CheckStock -->|Yes| ShowBorrow[Show Borrow Button]
    CheckStock -->|No| ShowNA[Show Not Available]
    
    ShowBorrow --> BorrowAction[User Click Borrow]
    BorrowAction --> BorrowFlow([Go to Borrowing Flow])
    
    UserAction -->|Staff/Admin:<br/>Add Book| AddBookForm[Show Add Book Form]
    AddBookForm --> InputBook[Input:<br/>- Judul<br/>- Cover/Image<br/>- Penulis<br/>- Deskripsi<br/>- ISBN<br/>- Kategori<br/>- Stock]
    
    InputBook --> ValidateBook{Validate<br/>Input}
    ValidateBook -->|Invalid| ShowError[Show Validation Error]
    ShowError --> AddBookForm
    ValidateBook -->|Valid| SaveBook[Save to Database]
    
    SaveBook --> Success[Show Success Message]
    Success --> RefreshList[Refresh Book List]
    RefreshList --> DisplayAll
    
    style Start fill:#4caf50
    style BorrowFlow fill:#ff9800
    style ShowRead fill:#2196f3
    style ShowBorrow fill:#ff5722
    style SaveBook fill:#9c27b0
```

---

## 4. Borrowing Flow (Detailed)

```mermaid
flowchart TD
    Start([User Click Borrow Button]) --> CheckAuth{User<br/>Logged In?}
    
    CheckAuth -->|No| RedirectLogin[Redirect to Login]
    RedirectLogin --> End1([End])
    
    CheckAuth -->|Yes| ShowBorrowForm[Show Borrowing Form]
    ShowBorrowForm --> InputDuration[User Input:<br/>- Borrow Duration Days<br/>- Notes Optional]
    
    InputDuration --> UserConfirm{User<br/>Confirm?}
    UserConfirm -->|No| Cancel[Cancel Borrowing]
    Cancel --> End2([End])
    
    UserConfirm -->|Yes| CreateRequest[Create Borrow Request]
    CreateRequest --> SaveRequest[Save to DB:<br/>- user_id<br/>- book_id<br/>- borrow_duration<br/>- status: pending<br/>- request_date]
    
    SaveRequest --> NotifyStaff[Notify Staff/Admin]
    NotifyStaff --> ShowSuccess[Show Success Message:<br/>Waiting for Approval]
    ShowSuccess --> End3([End - User Side])
    
    NotifyStaff --> StaffView[Staff/Admin Sees Request]
    StaffView --> StaffCheck{Staff<br/>Review}
    
    StaffCheck -->|Approve| CheckStockAgain{Stock<br/>Still Available?}
    CheckStockAgain -->|No| RejectAuto[Auto Reject:<br/>Stock Not Available]
    CheckStockAgain -->|Yes| UpdateApprove[Update Status: approved<br/>Set approved_date<br/>Set return_date<br/>Decrease Stock -1]
    
    UpdateApprove --> NotifyUserApprove[Notify User: Approved]
    NotifyUserApprove --> UserCanPickup[User Can Pick Up Book]
    UserCanPickup --> WaitReturn[Wait Until Return Date]
    
    StaffCheck -->|Reject| UpdateReject[Update Status: rejected<br/>Add rejection_reason]
    UpdateReject --> NotifyUserReject[Notify User: Rejected]
    
    WaitReturn --> CheckDate{Return Date<br/>Reached?}
    CheckDate -->|No| WaitReturn
    CheckDate -->|Yes| AutoReturn[Auto Return System Triggered]
    
    AutoReturn --> UpdateReturn[Update Status: returned<br/>Set actual_return_date<br/>Increase Stock +1]
    UpdateReturn --> NotifyUserReturn[Notify User: Book Returned]
    NotifyUserReturn --> End4([End])
    
    NotifyUserReject --> End5([End])
    RejectAuto --> NotifyUserReject
    
    style Start fill:#4caf50
    style End1 fill:#f44336
    style End2 fill:#f44336
    style End3 fill:#4caf50
    style End4 fill:#4caf50
    style End5 fill:#f44336
    style UpdateApprove fill:#8bc34a
    style UpdateReject fill:#ff5722
    style AutoReturn fill:#ff9800
```

---

## 5. Role & Permission Flow

```mermaid
flowchart TD
    Start([User Authenticated]) --> GetRole[Get User Role from Token/DB]
    
    GetRole --> CheckRole{User Role?}
    
    CheckRole -->|User/Peminjam| UserPerms[Permissions:<br/>- View Book List<br/>- Borrow Books<br/>- View Own Profile<br/>- Edit Own Profile<br/>- View Own Borrow History]
    
    CheckRole -->|Staff/Petugas| StaffPerms[Permissions:<br/>- All User Permissions<br/>- View Dashboard Statistics<br/>- Add Books from API<br/>- Manage Book Stock<br/>- Approve/Reject Borrows<br/>- Upload Promotions<br/>- Export Reports]
    
    CheckRole -->|Admin| AdminPerms[Permissions:<br/>- All Staff Permissions<br/>- Create Staff Account<br/>- Read Staff Accounts<br/>- Update Staff Account<br/>- Delete Staff Account<br/>- View All User Data]
    
    UserPerms --> UserNav[User Navigation:<br/>- Dashboard<br/>- My Borrowings<br/>- Profile]
    
    StaffPerms --> StaffNav[Staff Navigation:<br/>- Dashboard Stats<br/>- Book Management<br/>- Borrow Requests<br/>- Promotions<br/>- Reports]
    
    AdminPerms --> AdminNav[Admin Navigation:<br/>- All Staff Features<br/>- Staff Management<br/>- System Settings]
    
    UserNav & StaffNav & AdminNav --> AccessFeature[User Access Feature]
    
    AccessFeature --> ValidateAccess{Has<br/>Permission?}
    
    ValidateAccess -->|Yes| AllowAccess[Allow Access]
    ValidateAccess -->|No| DenyAccess[Show 403 Forbidden]
    
    AllowAccess --> End1([Feature Accessed])
    DenyAccess --> End2([Access Denied])
    
    style Start fill:#4caf50
    style UserPerms fill:#c8e6c9
    style StaffPerms fill:#b3e5fc
    style AdminPerms fill:#e1bee7
    style AllowAccess fill:#8bc34a
    style DenyAccess fill:#f44336
```

---

## 6. Database Schema Overview

```mermaid
erDiagram
    USERS ||--o{ BORROWINGS : creates
    BOOKS ||--o{ BORROWINGS : borrowed_in
    USERS ||--o{ PROMOTIONS : uploads
    
    USERS {
        uuid id PK
        string email UK
        string name
        enum role "admin, staff, user"
        string phone
        text address
        timestamp created_at
        timestamp updated_at
    }
    
    BOOKS {
        uuid id PK
        string title
        string cover_url
        string author
        text description
        string isbn UK
        string category
        int stock
        boolean can_read_online "false for local"
        enum source "local, gutendex"
        timestamp created_at
        timestamp updated_at
    }
    
    BORROWINGS {
        uuid id PK
        uuid user_id FK
        uuid book_id FK
        int borrow_duration_days
        enum status "pending, approved, rejected, returned"
        timestamp request_date
        timestamp approved_date
        timestamp return_date
        timestamp actual_return_date
        text rejection_reason
        text notes
        uuid approved_by FK "staff/admin id"
    }
    
    PROMOTIONS {
        uuid id PK
        string title
        text description
        string image_url
        uuid uploaded_by FK "staff/admin id"
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }
```

---

## 7. API Endpoints Structure

### Authentication
- `POST /api/auth/login` - Login user
- `POST /api/auth/register` - Register new user
- `POST /api/auth/logout` - Logout user
- `GET /api/auth/me` - Get current user info

### Books
- `GET /api/books` - Get all books (local + Gutendex)
- `GET /api/books/{id}` - Get book detail
- `POST /api/books` - Add new local book (Staff/Admin)
- `PUT /api/books/{id}` - Update book (Staff/Admin)
- `DELETE /api/books/{id}` - Delete book (Staff/Admin)
- `GET /api/books/gutendex` - Fetch from Gutendex API

### Borrowings
- `GET /api/borrowings` - Get borrowings (filtered by role)
- `GET /api/borrowings/{id}` - Get borrowing detail
- `POST /api/borrowings` - Create borrow request (User)
- `PUT /api/borrowings/{id}/approve` - Approve request (Staff/Admin)
- `PUT /api/borrowings/{id}/reject` - Reject request (Staff/Admin)
- `GET /api/borrowings/my` - Get user's own borrowings

### Users (Admin only)
- `GET /api/users` - Get all users
- `GET /api/users/staff` - Get staff accounts
- `POST /api/users/staff` - Create staff account
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

### Promotions (Staff/Admin)
- `GET /api/promotions` - Get all promotions
- `POST /api/promotions` - Upload promotion
- `PUT /api/promotions/{id}` - Update promotion
- `DELETE /api/promotions/{id}` - Delete promotion

### Reports (Staff/Admin)
- `GET /api/reports/borrowings` - Get borrowing statistics
- `GET /api/reports/export` - Export borrowing report

### Profile (All authenticated users)
- `GET /api/profile` - Get own profile
- `PUT /api/profile` - Update own profile

---

## 8. Frontend Component Structure

```
src/
├── components/
│   ├── common/
│   │   ├── Navbar.jsx
│   │   ├── Sidebar.jsx
│   │   ├── Footer.jsx
│   │   └── Loading.jsx
│   ├── auth/
│   │   ├── LoginForm.jsx
│   │   ├── RegisterForm.jsx
│   │   └── ProtectedRoute.jsx
│   ├── books/
│   │   ├── BookList.jsx
│   │   ├── BookCard.jsx
│   │   ├── BookDetail.jsx
│   │   ├── BookFilter.jsx
│   │   └── AddBookForm.jsx (Staff/Admin)
│   ├── borrowing/
│   │   ├── BorrowForm.jsx
│   │   ├── BorrowList.jsx
│   │   ├── BorrowCard.jsx
│   │   └── ApprovalModal.jsx (Staff/Admin)
│   ├── profile/
│   │   ├── ProfileView.jsx
│   │   └── ProfileEdit.jsx
│   ├── dashboard/
│   │   ├── UserDashboard.jsx
│   │   ├── StaffDashboard.jsx
│   │   └── AdminDashboard.jsx
│   ├── promotions/
│   │   ├── PromotionList.jsx
│   │   └── PromotionUpload.jsx (Staff/Admin)
│   └── reports/
│       └── ReportExport.jsx (Staff/Admin)
├── pages/
│   ├── Home.jsx
│   ├── Login.jsx
│   ├── Register.jsx
│   ├── Dashboard.jsx
│   ├── Books.jsx
│   ├── Borrowings.jsx
│   ├── Profile.jsx
│   ├── Promotions.jsx
│   └── StaffManagement.jsx (Admin)
├── services/
│   ├── api.js
│   ├── authService.js
│   ├── bookService.js
│   ├── borrowService.js
│   └── userService.js
├── contexts/
│   ├── AuthContext.jsx
│   └── ThemeContext.jsx
├── hooks/
│   ├── useAuth.js
│   ├── useBooks.js
│   └── useBorrowings.js
├── utils/
│   ├── constants.js
│   └── helpers.js
└── App.jsx
```

---

## Notes untuk Implementasi

### Frontend (React)
1. **State Management**: Gunakan Context API atau Zustand untuk global state
2. **HTTP Client**: Axios dengan interceptor untuk handle token
3. **Routing**: React Router v6 dengan protected routes
4. **UI Library**: Material-UI atau Tailwind CSS
5. **Form Handling**: React Hook Form dengan Yup validation

### Backend (Laravel)
1. **Authentication**: Laravel Sanctum untuk API tokens
2. **Middleware**: Custom middleware untuk role checking
3. **API Resources**: Transform response data
4. **Validation**: Form Request classes
5. **Jobs & Queues**: Untuk auto return books dan notifications

### Supabase Setup
1. **Auth**: Enable email/password authentication
2. **Database**: PostgreSQL dengan Row Level Security (RLS)
3. **Storage**: Untuk book covers dan promotion images
4. **Realtime**: Optional untuk live notifications

### Integrasi Gutendex API
1. Cache response untuk mengurangi API calls
2. Implement pagination
3. Handle API errors gracefully
4. Add fallback untuk offline mode
