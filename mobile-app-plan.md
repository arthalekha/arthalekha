# Arthalekha Mobile App Plan (Flutter)

## Overview

A fully self-contained Flutter mobile application for Arthalekha — a personal finance tracker. All data lives on-device in a local SQLite database. No backend server, no API calls, no internet required. The app handles all business logic locally including balance calculations, recurring transaction processing, dashboard aggregations, and CSV export. Registration, login features are excluded.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Flutter (latest stable) |
| State Management | Riverpod |
| Local Database | sqflite (SQLite) |
| Navigation | go_router (Flutter's built-in) |
| Charts | fl_chart (Flutter's built-in Canvas) |
| Date/Number Formatting | intl (ships with Flutter SDK) |
| CSV Generation | dart:io + dart:convert (no package needed) |
| File Sharing | Flutter's Share API (share_plus) |
| Path Access | path_provider (Flutter first-party) |
| Theming | Material 3 with forest-green custom theme |
| App Lock | local_auth (biometric/PIN via OS) |

> All dependencies are either Flutter SDK built-ins, first-party Flutter packages, or minimal single-purpose packages. No network-dependent packages are used.

---

## Theming

Two themes matching the web app:

| Token | Light | Dark |
|-------|-------|------|
| Primary | Deep forest green `#1a5c2e` | Vibrant green `#2a7a42` |
| Background | Off-white with green tint | Deep forest night `#1a2e1a` |
| Accent | Golden amber | Warm amber glow |
| Surface | Cream `#f2f5f0` | Dark bark `#1c2a1c` |

Theme toggle stored in SQLite `settings` table, matching `forest-light` / `forest-dark`.

---

## Local Database (SQLite)

All data persists in a single SQLite database file on device. The database is created on first launch via versioned migrations.

### Schema

```sql
-- Accounts
CREATE TABLE accounts (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT    NOT NULL,
    identifier  TEXT,
    account_type TEXT   NOT NULL, -- CA, SB, CC, WL, IN, LN, OT
    current_balance REAL NOT NULL DEFAULT 0,
    initial_date TEXT   NOT NULL, -- ISO 8601 date
    initial_balance REAL NOT NULL DEFAULT 0,
    data        TEXT,             -- JSON string for type-specific config
    deleted_at  TEXT,             -- ISO 8601 datetime, NULL if active
    created_at  TEXT    NOT NULL,
    updated_at  TEXT    NOT NULL
);

-- Balances (monthly snapshots)
CREATE TABLE balances (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    account_id  INTEGER NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    balance     REAL    NOT NULL,
    recorded_until TEXT NOT NULL, -- ISO 8601 date (month-end)
    created_at  TEXT    NOT NULL,
    updated_at  TEXT    NOT NULL,
    UNIQUE(account_id, recorded_until)
);

-- People
CREATE TABLE people (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT    NOT NULL,
    nick_name   TEXT,
    created_at  TEXT    NOT NULL,
    updated_at  TEXT    NOT NULL
);

-- Tags
CREATE TABLE tags (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT    NOT NULL UNIQUE,
    color       TEXT    NOT NULL, -- hex string e.g. #FF5733
    created_at  TEXT    NOT NULL,
    updated_at  TEXT    NOT NULL
);

-- Incomes
CREATE TABLE incomes (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    account_id  INTEGER NOT NULL REFERENCES accounts(id),
    person_id   INTEGER REFERENCES people(id) ON DELETE SET NULL,
    description TEXT    NOT NULL,
    transacted_at TEXT  NOT NULL, -- ISO 8601 datetime
    amount      REAL    NOT NULL,
    created_at  TEXT    NOT NULL,
    updated_at  TEXT    NOT NULL
);

-- Expenses
CREATE TABLE expenses (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    account_id  INTEGER NOT NULL REFERENCES accounts(id),
    person_id   INTEGER REFERENCES people(id) ON DELETE SET NULL,
    description TEXT    NOT NULL,
    transacted_at TEXT  NOT NULL,
    amount      REAL    NOT NULL,
    created_at  TEXT    NOT NULL,
    updated_at  TEXT    NOT NULL
);

-- Transfers
CREATE TABLE transfers (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    creditor_id INTEGER NOT NULL REFERENCES accounts(id),
    debtor_id   INTEGER NOT NULL REFERENCES accounts(id),
    description TEXT    NOT NULL,
    transacted_at TEXT  NOT NULL,
    amount      REAL    NOT NULL,
    created_at  TEXT    NOT NULL,
    updated_at  TEXT    NOT NULL
);

-- Recurring Incomes
CREATE TABLE recurring_incomes (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    account_id            INTEGER REFERENCES accounts(id),
    person_id             INTEGER REFERENCES people(id) ON DELETE SET NULL,
    description           TEXT    NOT NULL,
    amount                REAL    NOT NULL,
    next_transaction_at   TEXT    NOT NULL, -- ISO 8601 datetime
    frequency             TEXT    NOT NULL, -- daily, weekly, biweekly, monthly, quarterly, yearly
    remaining_recurrences INTEGER,          -- NULL = infinite
    created_at            TEXT    NOT NULL,
    updated_at            TEXT    NOT NULL
);

-- Recurring Expenses
CREATE TABLE recurring_expenses (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    account_id            INTEGER REFERENCES accounts(id),
    person_id             INTEGER REFERENCES people(id) ON DELETE SET NULL,
    description           TEXT    NOT NULL,
    amount                REAL    NOT NULL,
    next_transaction_at   TEXT    NOT NULL,
    frequency             TEXT    NOT NULL,
    remaining_recurrences INTEGER,
    created_at            TEXT    NOT NULL,
    updated_at            TEXT    NOT NULL
);

-- Recurring Transfers
CREATE TABLE recurring_transfers (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    creditor_id           INTEGER REFERENCES accounts(id),
    debtor_id             INTEGER REFERENCES accounts(id),
    description           TEXT    NOT NULL,
    amount                REAL    NOT NULL,
    next_transaction_at   TEXT    NOT NULL,
    frequency             TEXT    NOT NULL,
    remaining_recurrences INTEGER,
    created_at            TEXT    NOT NULL,
    updated_at            TEXT    NOT NULL
);

-- Taggables (polymorphic pivot)
CREATE TABLE taggables (
    tag_id        INTEGER NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    taggable_type TEXT    NOT NULL, -- 'income', 'expense', 'transfer', 'recurring_income', etc.
    taggable_id   INTEGER NOT NULL,
    UNIQUE(tag_id, taggable_id, taggable_type)
);
CREATE INDEX idx_taggables_type_id ON taggables(taggable_type, taggable_id);

-- Settings (key-value)
CREATE TABLE settings (
    key   TEXT PRIMARY KEY,
    value TEXT NOT NULL
);
```

### Database Helper

```dart
class DatabaseHelper {
    static const int version = 1;
    static const String dbName = 'arthalekha.db';

    Future<Database> open();          // Open or create DB
    Future<void> onCreate(db, ver);   // Run all CREATE TABLE statements
    Future<void> onUpgrade(db, oldVer, newVer); // Versioned migrations
}
```

---

## Data Models

### Account

```dart
class Account {
    final int? id;
    final String name;
    final String? identifier;
    final AccountType accountType;
    final double currentBalance;
    final DateTime initialDate;
    final double initialBalance;
    final Map<String, dynamic>? data; // type-specific config
    final DateTime? deletedAt;
    final DateTime createdAt;
    final DateTime updatedAt;

    Map<String, dynamic> toMap();
    factory Account.fromMap(Map<String, dynamic> map);
}
```

### AccountType (Enum)

```dart
enum AccountType {
    cash('CA', 'Cash'),
    savings('SB', 'Savings'),
    creditCard('CC', 'Credit Card'),
    wallet('WL', 'Wallet'),
    investment('IN', 'Investment'),
    loan('LN', 'Loan'),
    other('OT', 'Other');

    final String code;
    final String label;
    const AccountType(this.code, this.label);
}
```

**Savings Account Data** (stored in `data` JSON):
- `rate_of_interest` — 0-100%
- `interest_frequency` — Frequency enum value
- `average_balance_frequency` — Frequency enum value
- `average_balance_amount` — numeric

**Credit Card Account Data** (stored in `data` JSON):
- `rate_of_interest` — 0-100%
- `interest_frequency` — Frequency enum value
- `bill_generated_on` — day of month 1-31
- `repayment_of_bill_after_days` — 1-60
- `credit_limit` — numeric

### Frequency (Enum)

```dart
enum Frequency {
    daily('daily', 'Daily'),
    weekly('weekly', 'Weekly'),
    biweekly('biweekly', 'Biweekly'),
    monthly('monthly', 'Monthly'),
    quarterly('quarterly', 'Quarterly'),
    yearly('yearly', 'Yearly');

    final String value;
    final String label;
    const Frequency(this.value, this.label);

    DateTime addToDate(DateTime date);      // Add 1 interval
    DateTime startOfPeriod(DateTime date);  // Start of current period
}
```

### Income / Expense

```dart
class Income {
    final int? id;
    final int accountId;
    final int? personId;
    final String description;
    final DateTime transactedAt;
    final double amount;
    final DateTime createdAt;
    final DateTime updatedAt;

    // Loaded via joins / separate queries
    Account? account;
    Person? person;
    List<Tag> tags;
}
// Expense has the same shape
```

### Transfer

```dart
class Transfer {
    final int? id;
    final int creditorId;
    final int debtorId;
    final String description;
    final DateTime transactedAt;
    final double amount;
    final DateTime createdAt;
    final DateTime updatedAt;

    Account? creditor;
    Account? debtor;
    List<Tag> tags;
}
```

### RecurringIncome / RecurringExpense

```dart
class RecurringIncome {
    final int? id;
    final int? accountId;
    final int? personId;
    final String description;
    final double amount;
    final DateTime nextTransactionAt;
    final Frequency frequency;
    final int? remainingRecurrences; // null = infinite
    final DateTime createdAt;
    final DateTime updatedAt;

    Account? account;
    Person? person;
    List<Tag> tags;
}
// RecurringExpense has the same shape
```

### RecurringTransfer

```dart
class RecurringTransfer {
    final int? id;
    final int? creditorId;
    final int? debtorId;
    final String description;
    final double amount;
    final DateTime nextTransactionAt;
    final Frequency frequency;
    final int? remainingRecurrences;
    final DateTime createdAt;
    final DateTime updatedAt;

    Account? creditor;
    Account? debtor;
    List<Tag> tags;
}
```

### Tag

```dart
class Tag {
    final int? id;
    final String name;
    final String color; // hex string
}
```

### Person

```dart
class Person {
    final int? id;
    final String name;
    final String? nickName;
}
```

### Balance

```dart
class Balance {
    final int? id;
    final int accountId;
    final double balance;
    final DateTime recordedUntil;
}
```

---

## Repository Layer (Data Access)

Each entity has a repository that encapsulates all SQLite queries. No raw SQL leaks into screens or providers.

```
lib/data/repositories/
    account_repository.dart
    balance_repository.dart
    income_repository.dart
    expense_repository.dart
    transfer_repository.dart
    recurring_income_repository.dart
    recurring_expense_repository.dart
    recurring_transfer_repository.dart
    tag_repository.dart
    person_repository.dart
    settings_repository.dart
```

### AccountRepository

```dart
class AccountRepository {
    Future<List<Account>> getAll({AccountType? type, String? search, bool includeTrashed = false});
    Future<Account?> getById(int id);
    Future<int> create(Account account);     // Also creates initial balance entries
    Future<void> update(Account account);
    Future<void> softDelete(int id);         // Sets deleted_at
    Future<void> restore(int id);            // Clears deleted_at
    Future<void> incrementBalance(int accountId, double amount);
    Future<void> decrementBalance(int accountId, double amount);
}
```

### IncomeRepository / ExpenseRepository

```dart
class IncomeRepository {
    Future<List<Income>> getAll({
        int limit = 20, int offset = 0,
        DateTime? fromDate, DateTime? toDate,
        int? accountId, int? personId, int? tagId,
        String? search,
    });
    Future<Income?> getById(int id);
    Future<int> create(Income income, List<int> tagIds);
    Future<void> update(Income income, List<int> tagIds);
    Future<void> delete(int id);
    Future<List<Income>> getByAccountId(int accountId, {int limit, int offset});
    Future<List<Map<String, dynamic>>> getDailyTotals(int year, int month);
}
```

### TransferRepository

```dart
class TransferRepository {
    Future<List<Transfer>> getAll({
        int limit, int offset,
        DateTime? fromDate, DateTime? toDate,
        int? debtorId, int? creditorId, int? tagId,
        String? search,
    });
    Future<Transfer?> getById(int id);
    Future<int> create(Transfer transfer, List<int> tagIds);
    Future<void> update(Transfer transfer, List<int> tagIds);
    Future<void> delete(int id);
    Future<List<Transfer>> getByAccountId(int accountId, {int limit, int offset});
}
```

### BalanceRepository

```dart
class BalanceRepository {
    Future<List<Balance>> getByAccountId(int accountId);
    Future<void> upsert(int accountId, DateTime recordedUntil, double balance);
    Future<void> createInitialEntries(Account account);
    Future<void> incrementFutureBalances(int accountId, DateTime fromDate, double amount);
    Future<void> decrementFutureBalances(int accountId, DateTime fromDate, double amount);
}
```

### TagRepository

```dart
class TagRepository {
    Future<List<Tag>> getAll();
    Future<int> create(Tag tag);
    Future<void> update(Tag tag);
    Future<void> delete(int id);             // Cascades to taggables
    Future<List<Tag>> getForEntity(String type, int id);
    Future<void> syncTags(String type, int entityId, List<int> tagIds);
}
```

---

## Business Logic Services

All business logic that was in the Laravel backend services is reimplemented locally in Dart.

```
lib/services/
    account_service.dart
    balance_service.dart
    income_service.dart
    expense_service.dart
    transfer_service.dart
    recurring_transaction_service.dart
    dashboard_service.dart
    projected_dashboard_service.dart
    account_projected_balance_service.dart
    average_balance_service.dart
    export_service.dart
    backup_service.dart
```

### AccountService

```dart
class AccountService {
    /// Creates account + initial balance entries in a transaction
    Future<Account> createAccount(Account account);

    /// Updates account metadata
    Future<void> updateAccount(Account account);

    /// Soft deletes account (sets deleted_at)
    Future<void> deleteAccount(int id);

    /// Restores soft-deleted account
    Future<void> restoreAccount(int id);
}
```

### BalanceService

```dart
class BalanceService {
    /// Creates monthly balance snapshots from initial_date to current month
    Future<void> createInitialBalanceEntries(Account account);

    /// Calculates balance for a specific date by replaying transactions
    Future<double> getBalanceForDate(Account account, DateTime date);

    /// Increments current_balance and all future monthly snapshots
    Future<void> incrementBalance(int accountId, DateTime fromDate, double amount);

    /// Decrements current_balance and all future monthly snapshots
    Future<void> decrementBalance(int accountId, DateTime fromDate, double amount);

    /// Records month-end balance for all accounts (called on month rollover)
    Future<void> recordMonthlyBalances();
}
```

### IncomeService / ExpenseService

```dart
class IncomeService {
    /// Creates income, syncs tags, increments account balance — all in a DB transaction
    Future<Income> createIncome(Income income, List<int> tagIds);

    /// Updates income, reverses old balance impact, applies new — all in a DB transaction
    Future<void> updateIncome(Income oldIncome, Income newIncome, List<int> tagIds);

    /// Deletes income, decrements account balance — all in a DB transaction
    Future<void> deleteIncome(Income income);
}
// ExpenseService mirrors this but decrements on create, increments on delete
```

### TransferService

```dart
class TransferService {
    /// Creates transfer: decrements debtor, increments creditor, updates balances
    Future<Transfer> createTransfer(Transfer transfer, List<int> tagIds);

    /// Reverses old transfer, applies new
    Future<void> updateTransfer(Transfer oldTransfer, Transfer newTransfer, List<int> tagIds);

    /// Reverses transfer balance impacts
    Future<void> deleteTransfer(Transfer transfer);
}
```

### RecurringTransactionService

Runs on app launch and periodically while the app is open. Processes all due recurring items.

```dart
class RecurringTransactionService {
    /// Checks all recurring incomes/expenses/transfers where next_transaction_at <= now.
    /// For each due item:
    ///   1. Creates actual transaction (income/expense/transfer)
    ///   2. Copies tags to the new transaction
    ///   3. Decrements remaining_recurrences (if set)
    ///   4. Deletes recurring item if remaining_recurrences <= 0
    ///   5. Otherwise advances next_transaction_at by frequency
    /// All within a DB transaction per recurring item.
    Future<int> processDueRecurringTransactions();
}
```

### DashboardService

```dart
class DashboardService {
    /// Returns daily income/expense totals for a given month
    /// Output: { days: [1..31], incomeData: [...], expenseData: [...],
    ///           totalIncome, totalExpense, netSavings }
    Future<DashboardData> getMonthlyDashboard(int year, int month);
}
```

### ProjectedDashboardService

```dart
class ProjectedDashboardService {
    /// Projects 12 months of income/expense from recurring items
    /// Iterates each recurring item, simulates future transactions respecting
    /// frequency and remaining_recurrences
    /// Output: { months: [...], incomeData: [...], expenseData: [...],
    ///           balanceData: [...], totalProjectedIncome, totalProjectedExpense, netSavings }
    Future<ProjectedDashboardData> getProjectedDashboard();
}
```

### AccountProjectedBalanceService

```dart
class AccountProjectedBalanceService {
    /// Calculates daily projected balance for an account over a date range
    /// Combines actual past transactions with projected recurring items
    /// Output: { dates, incomeData, expenseData, transferInData, transferOutData,
    ///           balanceData, averageBalanceData, summary }
    Future<AccountProjectionData> calculate(Account account, DateTime start, DateTime end);
}
```

### AverageBalanceService

```dart
class AverageBalanceService {
    /// Calculates average daily balance for a Savings account
    /// Uses frequency from account.data['average_balance_frequency']
    Future<double> calculate(Account account);
}
```

### ExportService

```dart
class ExportService {
    /// Generates CSV file from incomes (with filters) and returns file path
    Future<String> exportIncomesToCsv({DateTime? from, DateTime? to, int? accountId, int? personId, int? tagId});

    /// Generates CSV file from expenses (with filters)
    Future<String> exportExpensesToCsv({DateTime? from, DateTime? to, int? accountId, int? personId, int? tagId});

    /// Generates CSV file from transfers (with filters)
    Future<String> exportTransfersToCsv({DateTime? from, DateTime? to, int? debtorId, int? creditorId, int? tagId});
}
```

### BackupService

```dart
class BackupService {
    /// Exports entire database as a JSON file for backup
    /// Serializes all tables into a single JSON structure
    Future<String> exportBackup();

    /// Imports a JSON backup file, replacing all local data
    /// Validates structure before overwriting
    Future<void> importBackup(String filePath);
}
```

---

## Screen Map

### Bottom Navigation (4 tabs)

| Tab | Icon | Screen |
|-----|------|--------|
| Dashboard | `Icons.dashboard` | Monthly dashboard |
| Accounts | `Icons.account_balance_wallet` | Account list |
| Transactions | `Icons.swap_horiz` | Tabbed: Incomes / Expenses / Transfers |
| More | `Icons.more_horiz` | Recurring items, Tags, People, Settings |

---

## Screens & Features

### 1. Dashboard (`/`)

**Monthly Overview** — bar chart of daily income vs expense for the selected month.

- **Header**: Month/year selector (left/right arrows + tap to pick month)
- **Summary cards row** (horizontal scroll):
  - Total Income (green)
  - Total Expense (red)
  - Net Savings (blue or amber depending on +/-)
- **Chart**: Grouped bar chart — one group per day, green bar (income) and red bar (expense)
- Data queried locally: `SELECT date(transacted_at), SUM(amount) FROM incomes/expenses WHERE month = ? GROUP BY date`

### 2. Projected Dashboard (`/projected-dashboard`)

Accessible from Dashboard via a toggle or FAB.

- **Chart**: Line chart over 12 months — income line, expense line, cumulative balance line
- **Summary cards**: Total Projected Income, Expense, Net Savings
- **Monthly breakdown list**: Expandable tiles per month showing income/expense/balance
- Computed locally by iterating recurring items and simulating future dates

### 3. Accounts List (`/accounts`)

- **Filter bar**: Account type dropdown, name search field, show-deleted toggle
- **List**: Cards showing:
  - Account name + type badge (short code with color)
  - Identifier (if set, muted text)
  - Current balance (right-aligned, green if positive, red if negative)
- **FAB**: Create new account
- **Swipe actions**: Edit (left), Delete/Restore (right)
- **Tap**: Navigate to account detail

### 4. Account Detail (`/accounts/{id}`)

- **Header card**: Name, type, identifier, current balance (large)
- **Tabs**:
  - **Overview**: Type-specific metadata (interest rate, credit limit, etc.), average balance for Savings
  - **Balances**: Historical monthly balances as a line chart + list
  - **Transactions**: Combined income/expense/transfer list for this account
  - **Projected**: Daily projected balance chart with average balance line
- **Actions**: Edit (app bar), Delete/Restore

### 5. Account Create/Edit (`/accounts/create`, `/accounts/{id}/edit`)

- **Form fields**:
  - Name (text, required)
  - Identifier (text, optional)
  - Account Type (dropdown, required)
  - Initial Date (date picker, required on create)
  - Initial Balance (number, required on create)
- **Conditional fields** (appear based on account type):
  - **Savings**: Rate of interest, interest frequency, average balance frequency, average balance amount
  - **Credit Card**: Rate of interest, interest frequency, bill generated on (day picker 1-31), repayment days after bill, credit limit
- **On save**: Runs `AccountService.createAccount()` which creates the account + initial balance entries in a single DB transaction

### 6. Incomes List (`/incomes`)

- **Filter sheet** (bottom sheet, triggered by filter icon):
  - Date range: From / To date pickers
  - Account: Dropdown of accounts
  - Person: Dropdown of people
  - Tag: Multi-select chips
  - Search: Text field for description
- **List**: Cards showing:
  - Description (bold)
  - Amount (right-aligned, green)
  - Date (subtitle)
  - Account name + person name (chips/badges)
  - Tag chips (colored)
- **Pagination**: Infinite scroll (LIMIT/OFFSET queries)
- **FAB**: Create new income
- **Swipe**: Edit / Delete
- **App bar action**: Export CSV (generates file locally, opens share sheet)

### 7. Income Create/Edit (`/incomes/create`, `/incomes/{id}/edit`)

- **Form fields**:
  - Account (dropdown, required)
  - Person (dropdown, optional)
  - Description (text, required, max 255)
  - Date (date picker, required)
  - Amount (number, required, min 0.01)
  - Tags (multi-select chips from available tags)
- **Quick-add**: Option to save and create another
- **On save**: Runs `IncomeService.createIncome()` — inserts income, syncs tags, increments account balance, updates balance snapshots — all in one DB transaction

### 8. Income Detail (`/incomes/{id}`)

- **Card**: All fields displayed with account, person, tags resolved
- **Actions**: Edit, Delete

### 9. Expenses List (`/expenses`)

Same layout as Incomes List but amounts shown in red.

### 10. Expense Create/Edit (`/expenses/create`, `/expenses/{id}/edit`)

Same form as Income Create/Edit.

### 11. Expense Detail (`/expenses/{id}`)

Same as Income Detail.

### 12. Transfers List (`/transfers`)

- **Filter sheet**:
  - Date range: From / To
  - Debtor account: Dropdown
  - Creditor account: Dropdown
  - Tag: Multi-select
  - Search: Text
- **List**: Cards showing:
  - Description
  - Amount
  - Debtor → Creditor (with account names and arrow icon)
  - Date, tag chips
- **FAB**: Create, **Swipe**: Edit / Delete, **Export**: CSV

### 13. Transfer Create/Edit (`/transfers/create`, `/transfers/{id}/edit`)

- **Form fields**:
  - Debtor account (dropdown, required) — "From"
  - Creditor account (dropdown, required) — "To"
  - Description (text, required)
  - Date (date picker, required)
  - Amount (number, required, min 0.01)
  - Tags (multi-select)

### 14. Transfer Detail (`/transfers/{id}`)

Card layout with all fields + debtor/creditor account details.

### 15. Recurring Incomes List (`/recurring-incomes`)

- **Filter sheet**: Search, account, person, frequency dropdown
- **List**: Cards showing:
  - Description
  - Amount (green) + frequency badge
  - Next transaction date
  - Remaining recurrences (or "Infinite")
  - Account + person + tag chips
- **FAB**: Create, **Swipe**: Edit / Delete

### 16. Recurring Income Create/Edit

- **Form fields**:
  - Account (dropdown, optional)
  - Person (dropdown, optional)
  - Description (text, required)
  - Amount (number, required, min 0.01)
  - Next Transaction Date (date picker, required)
  - Frequency (dropdown: Daily, Weekly, Biweekly, Monthly, Quarterly, Yearly)
  - Remaining Recurrences (number, optional — blank = infinite)
  - Tags (multi-select)

### 17. Recurring Expenses List & Create/Edit

Same layout as Recurring Incomes with amounts in red.

### 18. Recurring Transfers List (`/recurring-transfers`)

- **Filter sheet**: Search, creditor, debtor, frequency
- **List**: Cards with description, amount, debtor → creditor, frequency badge, next date, remaining
- **FAB**: Create, **Swipe**: Edit / Delete

### 19. Recurring Transfer Create/Edit

- **Form fields**:
  - Debtor account (dropdown, optional)
  - Creditor account (dropdown, optional)
  - Description (text, required)
  - Amount (number, required)
  - Next Transaction Date (date picker, required)
  - Frequency (dropdown)
  - Remaining Recurrences (number, optional)
  - Tags (multi-select)

### 20. Tags List (`/tags`)

- **List**: Colored chips/cards with tag name + color swatch
- **FAB**: Create
- **Swipe**: Edit / Delete

### 21. Tag Create/Edit

- **Form fields**:
  - Name (text, required, max 255, unique)
  - Color (color picker, required — hex output)

### 22. People List (`/people`)

- **List**: Cards with name + nickname
- **FAB**: Create
- **Swipe**: Edit / Delete

### 23. Person Create/Edit

- **Form fields**:
  - Name (text, required, max 255)
  - Nickname (text, optional)

### 24. Settings (`/settings`)

- **Theme toggle**: Light / Dark
- **App lock**: Enable/disable biometric or device PIN lock via `local_auth`
- **Backup**: Export all data as JSON file (share sheet)
- **Restore**: Import JSON backup file (file picker)
- **About / Version info**

---

## Shared Components

| Component | Usage |
|-----------|-------|
| `AmountText` | Formatted currency display (green for income, red for expense) |
| `AccountTypeBadge` | Colored badge with 2-letter code |
| `FrequencyBadge` | Chip showing frequency label |
| `TagChips` | Row of colored tag chips |
| `FilterSheet` | Reusable bottom sheet for list filters |
| `DateRangePicker` | From/To date selection |
| `AccountDropdown` | Searchable account picker |
| `PersonDropdown` | Searchable person picker |
| `TagMultiSelect` | Multi-select tag chips |
| `EmptyState` | Illustration + message for empty lists |
| `ConfirmDialog` | Delete/restore confirmation |
| `MonthSelector` | Month/year picker with arrows |
| `SummaryCard` | Colored card with label + amount |
| `TransactionCard` | Reusable list tile for income/expense/transfer |

---

## Navigation Structure

```
/                              → Dashboard (Monthly)
/projected                     → Projected Dashboard (12-month)
/accounts                      → Account List
/accounts/create               → Account Create
/accounts/:id                  → Account Detail (tabbed)
/accounts/:id/edit             → Account Edit
/incomes                       → Income List
/incomes/create                → Income Create
/incomes/:id                   → Income Detail
/incomes/:id/edit              → Income Edit
/expenses                      → Expense List
/expenses/create               → Expense Create
/expenses/:id                  → Expense Detail
/expenses/:id/edit             → Expense Edit
/transfers                     → Transfer List
/transfers/create              → Transfer Create
/transfers/:id                 → Transfer Detail
/transfers/:id/edit            → Transfer Edit
/recurring-incomes             → Recurring Income List
/recurring-incomes/create      → Recurring Income Create
/recurring-incomes/:id         → Recurring Income Detail
/recurring-incomes/:id/edit    → Recurring Income Edit
/recurring-expenses            → Recurring Expense List
/recurring-expenses/create     → Recurring Expense Create
/recurring-expenses/:id        → Recurring Expense Detail
/recurring-expenses/:id/edit   → Recurring Expense Edit
/recurring-transfers           → Recurring Transfer List
/recurring-transfers/create    → Recurring Transfer Create
/recurring-transfers/:id       → Recurring Transfer Detail
/recurring-transfers/:id/edit  → Recurring Transfer Edit
/tags                          → Tag List
/tags/create                   → Tag Create
/tags/:id/edit                 → Tag Edit
/people                        → People List
/people/create                 → Person Create
/people/:id/edit               → Person Edit
/settings                      → Settings
```

---

## Project Structure

```
lib/
├── main.dart
├── app.dart                              # MaterialApp, theme, router
├── router.dart                           # go_router configuration
│
├── core/
│   ├── theme/
│   │   ├── app_theme.dart                # Light + dark ThemeData
│   │   └── colors.dart                   # Forest theme color constants
│   ├── constants.dart                    # Pagination size, date formats
│   ├── extensions/
│   │   ├── datetime_ext.dart             # Month arithmetic, formatting
│   │   └── double_ext.dart              # Currency formatting
│   └── utils/
│       ├── currency_formatter.dart
│       └── date_formatter.dart
│
├── data/
│   ├── database_helper.dart              # SQLite open, migrations
│   ├── models/
│   │   ├── account.dart
│   │   ├── account_type.dart
│   │   ├── balance.dart
│   │   ├── expense.dart
│   │   ├── frequency.dart
│   │   ├── income.dart
│   │   ├── person.dart
│   │   ├── recurring_expense.dart
│   │   ├── recurring_income.dart
│   │   ├── recurring_transfer.dart
│   │   ├── tag.dart
│   │   └── transfer.dart
│   └── repositories/
│       ├── account_repository.dart
│       ├── balance_repository.dart
│       ├── expense_repository.dart
│       ├── income_repository.dart
│       ├── person_repository.dart
│       ├── recurring_expense_repository.dart
│       ├── recurring_income_repository.dart
│       ├── recurring_transfer_repository.dart
│       ├── settings_repository.dart
│       ├── tag_repository.dart
│       └── transfer_repository.dart
│
├── services/
│   ├── account_service.dart
│   ├── average_balance_service.dart
│   ├── balance_service.dart
│   ├── backup_service.dart
│   ├── dashboard_service.dart
│   ├── expense_service.dart
│   ├── export_service.dart
│   ├── income_service.dart
│   ├── account_projected_balance_service.dart
│   ├── projected_dashboard_service.dart
│   ├── recurring_transaction_service.dart
│   └── transfer_service.dart
│
├── providers/
│   ├── account_provider.dart
│   ├── dashboard_provider.dart
│   ├── expense_provider.dart
│   ├── income_provider.dart
│   ├── person_provider.dart
│   ├── recurring_expense_provider.dart
│   ├── recurring_income_provider.dart
│   ├── recurring_transfer_provider.dart
│   ├── tag_provider.dart
│   ├── theme_provider.dart
│   └── transfer_provider.dart
│
├── screens/
│   ├── dashboard/
│   │   ├── dashboard_screen.dart
│   │   └── projected_dashboard_screen.dart
│   ├── accounts/
│   │   ├── account_list_screen.dart
│   │   ├── account_detail_screen.dart
│   │   └── account_form_screen.dart
│   ├── incomes/
│   │   ├── income_list_screen.dart
│   │   ├── income_detail_screen.dart
│   │   └── income_form_screen.dart
│   ├── expenses/
│   │   ├── expense_list_screen.dart
│   │   ├── expense_detail_screen.dart
│   │   └── expense_form_screen.dart
│   ├── transfers/
│   │   ├── transfer_list_screen.dart
│   │   ├── transfer_detail_screen.dart
│   │   └── transfer_form_screen.dart
│   ├── recurring/
│   │   ├── recurring_income_list_screen.dart
│   │   ├── recurring_income_form_screen.dart
│   │   ├── recurring_expense_list_screen.dart
│   │   ├── recurring_expense_form_screen.dart
│   │   ├── recurring_transfer_list_screen.dart
│   │   └── recurring_transfer_form_screen.dart
│   ├── tags/
│   │   ├── tag_list_screen.dart
│   │   └── tag_form_screen.dart
│   ├── people/
│   │   ├── person_list_screen.dart
│   │   └── person_form_screen.dart
│   └── settings/
│       └── settings_screen.dart
│
└── widgets/
    ├── amount_text.dart
    ├── account_type_badge.dart
    ├── frequency_badge.dart
    ├── tag_chips.dart
    ├── filter_sheet.dart
    ├── date_range_picker.dart
    ├── account_dropdown.dart
    ├── person_dropdown.dart
    ├── tag_multi_select.dart
    ├── empty_state.dart
    ├── confirm_dialog.dart
    ├── month_selector.dart
    ├── summary_card.dart
    └── transaction_card.dart
```

---

## Validation Rules

All validation is enforced locally before writing to SQLite:

| Field | Rule |
|-------|------|
| `name` (account, tag, person) | Required, max 255 |
| `identifier` | Optional, max 255 |
| `account_type` | Required, one of enum values |
| `initial_date` | Required, valid date |
| `initial_balance` | Required, numeric |
| `description` | Required, max 255 |
| `transacted_at` | Required, valid date |
| `amount` | Required, > 0, max 999999999999 |
| `account_id` | Required for income/expense, must exist in accounts |
| `creditor_id` / `debtor_id` | Required for transfer, must exist in accounts |
| `frequency` | Required for recurring items, one of enum values |
| `remaining_recurrences` | Optional, min 1 |
| `next_transaction_at` | Required for recurring items |
| `color` (tag) | Required, valid hex |
| `data.*` fields | Conditional on account type (see AccountType section) |

---

## Recurring Transaction Processing

Since there is no server-side job queue, recurring transactions are processed locally:

1. **On app launch**: `RecurringTransactionService.processDueRecurringTransactions()` runs immediately
2. **While app is open**: A periodic timer (every 15 minutes) checks for newly due items
3. **Processing logic** (per due recurring item, inside a DB transaction):
   - Create actual income/expense/transfer record with same data
   - Copy tags from recurring item to new transaction
   - Update account balances accordingly
   - If `remaining_recurrences` is set, decrement it
   - If `remaining_recurrences <= 0`, delete the recurring item
   - Otherwise, advance `next_transaction_at` by one frequency interval
4. **Month rollover**: When the app detects a new month has started, call `BalanceService.recordMonthlyBalances()` to snapshot all account balances

---

## Data Backup & Restore

Since all data is local, backup/restore is critical.

### Export (Backup)
- Serializes all tables into a single JSON structure with a version field
- JSON structure: `{ version: 1, exported_at: "...", accounts: [...], balances: [...], incomes: [...], ... }`
- Writes to a temporary file, then opens the OS share sheet so the user can save to Files, Google Drive, email, etc.

### Import (Restore)
- Opens file picker to select a JSON backup file
- Validates the JSON structure and version
- Shows a confirmation dialog warning that all current data will be replaced
- Drops and recreates all table data within a single DB transaction
- Recalculates all account current_balance values after import

---

## Implementation Phases

### Phase 1 — Foundation
- Project setup, theming (light/dark), go_router navigation
- SQLite database helper with versioned migrations
- All data models with `toMap()` / `fromMap()`
- All repositories with basic CRUD
- Shared widgets (AmountText, badges, dropdowns, empty states)

### Phase 2 — Accounts
- Account list with filters (type, name, trashed)
- Account create/edit forms with conditional fields per type
- Account detail with tabs (overview, balances chart, transactions, projected balance)
- Soft delete + restore
- BalanceService: initial entries, increment/decrement, monthly snapshots

### Phase 3 — Transactions
- Income list, create/edit, detail (with balance updates)
- Expense list, create/edit, detail (with balance updates)
- Transfer list, create/edit, detail (with dual balance updates)
- Tag multi-select in all forms
- CSV export via ExportService + share sheet

### Phase 4 — Recurring Items
- Recurring income/expense/transfer list + form
- Frequency picker, recurrence limit input
- RecurringTransactionService: processing due items on app launch + periodic timer

### Phase 5 — Dashboards
- Monthly dashboard with bar chart + summary cards + month navigation
- Projected dashboard with line chart + monthly breakdown
- Account projected balance chart with average balance line

### Phase 6 — People, Tags & Settings
- People CRUD
- Tag CRUD with color picker
- Settings: theme toggle, app lock (biometric/PIN)
- Backup export / restore import
- Infinite scroll pagination on all lists
- Loading states, error handling, haptic feedback
