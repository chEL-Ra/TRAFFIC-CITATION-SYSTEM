<?php
require 'db.php';
session_start();

if (isset($_GET['tct_no'])) {
    $tct = $_GET['tct_no'];

    // 1. Verify it is actually paid by checking the payments table
    // We join 'payments' because 'citations' doesn't have the status column
    $check = $pdo->prepare("
        SELECT p.status 
        FROM citations c 
        JOIN payments p ON c.tct_no = p.tct_no 
        WHERE c.tct_no = ?
    ");
    $check->execute([$tct]);
    $currentStatus = $check->fetchColumn();

    // 2. Only allow deletion if the status is 'Paid'
    if ($currentStatus === 'Paid') {
        
        // Start a transaction to ensure both table updates succeed or fail together
        $pdo->beginTransaction();

        try {
            // Remove from ticket_violations first (Foreign Key constraint safety)
            $stmt1 = $pdo->prepare("DELETE FROM ticket_violations WHERE tct_no = ?");
            $stmt1->execute([$tct]);

            // Remove from citations
            $stmt2 = $pdo->prepare("DELETE FROM citations WHERE tct_no = ?");
            $stmt2->execute([$tct]);

            // Remove from payments
            $stmt3 = $pdo->prepare("DELETE FROM payments WHERE tct_no = ?");
            $stmt3->execute([$tct]);

            $pdo->commit();
            header("Location: dashboard.php?page=view_offenses&msg=deleted");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            die("Error: Could not remove record. " . $e->getMessage());
        }
        
    } else {
        // Redirect if trying to delete an unpaid record
        header("Location: dashboard.php?page=view_offenses&msg=error_unpaid");
        exit();
    }
} else {
    header("Location: dashboard.php?page=view_offenses");
    exit();
}