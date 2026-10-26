<?php

// 1. Connect to database
$conn = new mysqli("localhost", "root", "password", "database_name");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

echo "Database connected successfully!\n";

// 2. Create User class
class User
{
    private $conn;
    private $name;
    private $accountNumber;
    private $email;
    private $balance;

    // Constructor
    public function __construct($conn, $name, $accountNumber, $email, $balance)
    {
        $this->conn = $conn;
        $this->name = $name;
        $this->accountNumber = $accountNumber;
        $this->email = $email;
        $this->balance = $balance;
    }

    // CREATE: Add customer
    public function addCustomer()
    {
        $sql = "INSERT INTO customers
                (name, account_number, email, balance)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sssd",
            $this->name,
            $this->accountNumber,
            $this->email,
            $this->balance
        );

        if ($stmt->execute()) {
            echo "Customer added successfully!\n";
        } else {
            echo "Error: " . $stmt->error . "\n";
        }

        $stmt->close();
    }

    // READ: Display all customers
    public function displayCustomers()
    {
        $result = $this->conn->query("SELECT * FROM customers");

        if ($result->num_rows == 0) {
            echo "No customers found.\n";
            return;
        }

        while ($row = $result->fetch_assoc()) {
            echo "\nID: " . $row['id'];
            echo "\nName: " . $row['name'];
            echo "\nAccount Number: " . $row['account_number'];
            echo "\nEmail: " . $row['email'];
            echo "\nBalance: Rs. " . $row['balance'];
            echo "\n-------------------------\n";
        }
    }

    // READ: Display customer by ID
    public function displayCustomerByID($id)
    {
        $sql = "SELECT * FROM customers WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            echo "\nID: " . $row['id'];
            echo "\nName: " . $row['name'];
            echo "\nAccount Number: " . $row['account_number'];
            echo "\nEmail: " . $row['email'];
            echo "\nBalance: Rs. " . $row['balance'] . "\n";
        } else {
            echo "Customer not found.\n";
        }

        $stmt->close();
    }

    // UPDATE: Update customer
    public function updateCustomer($id, $name, $accountNumber, $email, $balance)
    {
        $sql = "UPDATE customers
                SET name = ?, account_number = ?, email = ?, balance = ?
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sssdi",
            $name,
            $accountNumber,
            $email,
            $balance,
            $id
        );

        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Customer updated successfully!\n";
        } else {
            echo "No changes made or customer not found.\n";
        }

        $stmt->close();
    }

    // DELETE: Delete customer
    public function deleteCustomer($id)
    {
        $sql = "DELETE FROM customers WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Customer deleted successfully!\n";
        } else {
            echo "Customer not found.\n";
        }

        $stmt->close();
    }

    // CREDIT: Deposit money
    public function credit($amount)
    {
        if ($amount <= 0) {
            echo "Amount must be greater than zero.\n";
            return;
        }

        $sql = "UPDATE customers
                SET balance = balance + ?
                WHERE account_number = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ds", $amount, $this->accountNumber);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Amount credited successfully!\n";
        } else {
            echo "Customer not found or no balance change.\n";
        }

        $stmt->close();
    }

    // DEBIT: Withdraw money
    public function debit($amount)
    {
        if ($amount <= 0) {
            echo "Amount must be greater than zero.\n";
            return;
        }

        $sql = "UPDATE customers
                SET balance = balance - ?
                WHERE account_number = ? AND balance >= ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("dsd", $amount, $this->accountNumber, $amount);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Amount debited successfully!\n";
        } else {
            echo "Customer not found or insufficient balance.\n";
        }

        $stmt->close();
    }
}

// 3. Get terminal input
function getInput($message)
{
    echo $message;
    return trim(fgets(STDIN));
}

// 4. Display menu
do {
    echo "\n===== HORIZON BANK =====\n";
    echo "1. Add Customer\n";
    echo "2. Display All Customers\n";
    echo "3. Display Customer by ID\n";
    echo "4. Update Customer\n";
    echo "5. Delete Customer\n";
    echo "6. Credit Money\n";
    echo "7. Debit Money\n";
    echo "8. Exit\n";

    $choice = getInput("Enter your choice: ");

    switch ($choice) {

        case 1:
            $name = getInput("Enter name: ");
            $account = getInput("Enter account number: ");
            $email = getInput("Enter email: ");
            $balance = getInput("Enter opening balance: ");

            if (!is_numeric($balance) || $balance < 0) {
                echo "Enter a valid balance.\n";
                break;
            }

            $user = new User(
                $conn, $name, $account, $email, (float)$balance
            );
            $user->addCustomer();
            break;

        case 2:
            $user = new User($conn, "", "", "", 0);
            $user->displayCustomers();
            break;

        case 3:
            $id = getInput("Enter customer ID: ");

            if (!ctype_digit($id) || (int)$id < 1) {
                echo "Enter a valid customer ID.\n";
                break;
            }

            $user = new User($conn, "", "", "", 0);
            $user->displayCustomerByID((int)$id);
            break;

        case 4:
            $id = getInput("Enter customer ID to update: ");
            $name = getInput("Enter new name: ");
            $account = getInput("Enter new account number: ");
            $email = getInput("Enter new email: ");
            $balance = getInput("Enter new balance: ");

            if (!ctype_digit($id) || (int)$id < 1 ||
                !is_numeric($balance) || $balance < 0) {
                echo "Enter a valid ID and balance.\n";
                break;
            }

            $user = new User($conn, $name, $account, $email, (float)$balance);

            $user->updateCustomer(
                (int)$id, $name, $account, $email, (float)$balance
            );
            break;

        case 5:
            $id = getInput("Enter customer ID to delete: ");

            if (!ctype_digit($id) || (int)$id < 1) {
                echo "Enter a valid customer ID.\n";
                break;
            }

            $user = new User($conn, "", "", "", 0);
            $user->deleteCustomer((int)$id);
            break;

        case 6:
            $account = getInput("Enter account number: ");
            $amount = getInput("Enter deposit amount: ");

            if (!is_numeric($amount) || $amount <= 0) {
                echo "Enter a valid positive amount.\n";
                break;
            }

            $user = new User($conn, "", $account, "", 0);
            $user->credit((float)$amount);
            break;

        case 7:
            $account = getInput("Enter account number: ");
            $amount = getInput("Enter withdrawal amount: ");

            if (!is_numeric($amount) || $amount <= 0) {
                echo "Enter a valid positive amount.\n";
                break;
            }

            $user = new User($conn, "", $account, "", 0);
            $user->debit((float)$amount);
            break;

        case 8:
            echo "Thank you for using Horizon Bank!\n";
            break;

        default:
            echo "Invalid choice. Please try again.\n";
    }

} while ($choice != "8");

// 5. Close database connection
$conn->close();

?>